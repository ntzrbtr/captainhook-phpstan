<?php

declare(strict_types=1);

namespace Netzarbeiter\CaptainHookPhpstan\Action;

use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Exception\ActionFailed;
use CaptainHook\App\Hook\Action;
use CaptainHook\App\Hook\Constrained;
use CaptainHook\App\Hook\Restriction;
use CaptainHook\App\Hooks;
use Netzarbeiter\CaptainHookPhpstan\PathFilter;
use SebastianFeldmann\Cli\Processor\ProcOpen as Processor;
use SebastianFeldmann\Git\Repository;

/**
 * Runs PHPStan on all staged PHP files that the project's PHPStan configuration covers.
 *
 * Which files are covered is taken from `phpstan dump-parameters`, so `paths`, `excludePaths`,
 * includes and placeholders are resolved by PHPStan itself.
 *
 * Options:
 *  - phpstan:       PHPStan executable (default: vendor/bin/phpstan)
 *  - configuration: PHPStan configuration file (default: PHPStan's own lookup)
 *  - memory-limit:  passed to PHPStan as --memory-limit
 */
class AnalyseStagedFiles implements Action, Constrained
{
    public static function getRestriction(): Restriction
    {
        return Restriction::fromArray([Hooks::PRE_COMMIT]);
    }

    public function execute(Config $config, IO $io, Repository $repository, Config\Action $action): void
    {
        $stagedFiles = $this->getStagedPhpFiles($repository);
        if ($stagedFiles === []) {
            $io->write('  <comment>No staged PHP files, skipping PHPStan</comment>', true, IO::VERBOSE);
            return;
        }

        $phpstan = $this->stringOption($action, 'phpstan', 'vendor/bin/phpstan');
        $commonArgs = [];
        if ($this->stringOption($action, 'configuration') !== '') {
            $commonArgs[] = '--configuration=' . escapeshellarg($this->stringOption($action, 'configuration'));
        }
        if ($this->stringOption($action, 'memory-limit') !== '') {
            $commonArgs[] = '--memory-limit=' . escapeshellarg($this->stringOption($action, 'memory-limit'));
        }

        $filter = PathFilter::fromParameters($this->dumpParameters($phpstan, $commonArgs));
        $files = $filter->filter($stagedFiles);
        if ($files === []) {
            $io->write('  <comment>No staged files covered by the PHPStan configuration</comment>', true, IO::VERBOSE);
            return;
        }

        $io->write('  Analysing ' . count($files) . ' file(s) with PHPStan', true, IO::VERBOSE);

        $result = (new Processor())->run($this->buildCommand(
            $phpstan,
            array_merge(['analyse', '--no-progress', '--no-interaction'], $commonArgs, array_map('escapeshellarg', $files))
        ));

        if (!$result->isSuccessful()) {
            throw new ActionFailed(trim($result->getStdOut() . PHP_EOL . $result->getStdErr()));
        }
    }

    /**
     * @return string[] Absolute paths of added, copied, modified and renamed PHP files
     */
    private function getStagedPhpFiles(Repository $repository): array
    {
        $root = $repository->getRoot();
        $files = [];
        foreach ($repository->getIndexOperator()->getStagedFilesOfType('php', ['A', 'C', 'M', 'R']) as $file) {
            $path = realpath($root . DIRECTORY_SEPARATOR . $file);
            if ($path !== false && is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * @param  string[] $commonArgs
     * @return array<mixed>
     * @throws ActionFailed
     */
    private function dumpParameters(string $phpstan, array $commonArgs): array
    {
        $result = (new Processor())->run(
            $this->buildCommand($phpstan, array_merge(['dump-parameters', '--json'], $commonArgs))
        );

        $parameters = json_decode($result->getStdOut(), true);
        if (!$result->isSuccessful() || !is_array($parameters)) {
            throw new ActionFailed(
                'Could not read PHPStan configuration via dump-parameters:' . PHP_EOL
                . trim($result->getStdOut() . PHP_EOL . $result->getStdErr())
            );
        }

        return $parameters;
    }

    private function stringOption(Config\Action $action, string $name, string $default = ''): string
    {
        $value = $action->getOptions()->get($name, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param string[] $args Already escaped arguments
     */
    private function buildCommand(string $phpstan, array $args): string
    {
        return escapeshellarg($phpstan) . ' ' . implode(' ', $args);
    }
}
