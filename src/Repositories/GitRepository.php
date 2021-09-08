<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\ExceptionHandler;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

class GitRepository
{

    /**
     * The working directory or null to use the working dir of the current PHP process.
     *
     * @var string
     */
    protected $workingTree;

    /**
     * The remote url of the git repository.
     *
     * @var string
     */
    protected $url;

    /**
     * @param string|null $workingTree
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     * @throws InvalidPathException
     */
    public function __construct(string $workingTree = null)
    {
        $this->workingTree = $this->getLocalRootPath($workingTree ?? getcwd());
    }

    /**
     * @return string
     */
    public function getWorkingTree(): string
    {
        return $this->workingTree;
    }


    /**
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     * @throws InvalidPathException
     */
    public function getLocalRootPath(string $workingTree = null): string
    {
        return $this->execute('git rev-parse --show-toplevel', $workingTree ?? $this->workingTree);
    }

    /**
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getRemoteUrl(string $workingTree = null): string
    {
        if (!$this->url || $this->workingTree != $workingTree) {
            $url = $this->execute('git config --get remote.origin.url', $workingTree ?? $this->workingTree);

            if ($this->workingTree == $workingTree) {
                $this->url = $url;
            }
        }

        return $url ?? $this->url;
    }

    /**
     * @param string|null $url
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function parseNameFromUrl(string $url = null): string
    {
        return basename($url ?? $this->getRemoteUrl(), '.git');
    }

    /**
     * @param string|null $url
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function cloneToTemp(string $url = null): string
    {
        $url = $url ?? $this->getRemoteUrl();
        $directory = $this->execute('mktemp -d');

        $this->process('git clone ' . $url ?? $this->getRemoteUrl(), $directory);

        $directory .= DIRECTORY_SEPARATOR . $this->parseNameFromUrl();

        if ($url == $this->getRemoteUrl()) {
            $this->workingTree = $directory;
        }

        return $directory;
    }

    /**
     * @param string|null $workingTree
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function refreshOriginMetadata(string $workingTree = null)
    {
        $this->execute('git fetch origin', $workingTree ?? $this->workingTree);
    }

    /**
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getCurrentBranch(string $workingTree = null): string
    {
        return basename($this->execute('git symbolic-ref -q HEAD', $workingTree ?? $this->workingTree));
    }

//    /**
//     * @param string $branch
//     * @param string|null $workingTree
//     * @return bool
//     * @throws InvalidGitRepositoryException
//     * @throws InvalidPathException
//     * @throws ProcessException
//     */
//    public function hasBranch(string $branch, string $workingTree = null): bool
//    {
//        return (bool)$this->execute(
//            'git ls-remote --heads origin ' . $branch,
//            $workingTree ?? $this->workingTree
//        );
//    }

    /**
     * @param string $branch
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getCurrentTag(string $branch, string $workingTree = null): string
    {
        $workingTree = $workingTree ?? $this->workingTree;
        $currentBranch = $this->getCurrentBranch($workingTree);


        if($currentBranch != $branch) {
            $this->checkoutBranch($branch, $workingTree);
        }

        $tag = $this->execute('git describe', $workingTree);

        if($currentBranch != $branch) {
            $this->checkoutBranch($currentBranch, $workingTree);
        }

        return $tag;
    }

    /**
     * @param string $releaseTag
     * @return array
     */
    public function parseVersionDetailsFromTag(string $releaseTag): array
    {
        $position = strpos($releaseTag, '-');
        $version = substr($releaseTag, 0, $position ?: strlen($releaseTag));
        $versionParts = explode('.', $version);
        $metadata = $position ? substr($releaseTag, $position + 1) : null;

        if ($metadata) {
            $position = strrpos($metadata, '.');
            $revision = substr($metadata, $position + 1);
            $metadata = substr($metadata, 0, $position);

            preg_match('/^(alpha|beta|jira|rc)?-?(.*)\.?([0-9]{0,3})$/',
                $metadata,
                $metadataParts,
                PREG_OFFSET_CAPTURE);


            $descriptor = $metadataParts[2][0] ?? null;

            // If the revision contains non-num
            if($revision && !is_int($revision)) {
                $revisionParts = explode('-', $revision);
                $revision = $revisionParts ? array_shift($revisionParts) : null;

                $descriptor .= $revisionParts ? implode('-', $revisionParts) : null;
            }
        }

        return [
            'major' => $versionParts[0] ?? 0,
            'minor' => $versionParts[1] ?? 0,
            'patch' => $versionParts[2] ?? 0,
            'type' => $metadataParts[1][0] ?? null,
            'descriptor' => $descriptor ?? null,
            'revision' => $revision ?? 0,
        ];
    }

    /**
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function hasLocalChanges(string $workingTree = null): string
    {
        return (bool)$this->execute('git status --porcelain', $workingTree ?? $this->workingTree);
    }

    /**
     * @param string $branch
     * @param string|null $workingTree
     * @return GitRepository
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function checkoutBranch(string $branch, string $workingTree = null): self
    {
        $this->process(
            'git -c advice.detachedHead=false checkout --quiet ' . $branch,
            $workingTree ?? $this->workingTree);

        return $this;
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws ProcessException|InvalidPathException
     */
    private function execute(string $command, string $cwd = null): string
    {
        return trim($this->process($command, $cwd)->getOutput());
    }

    /**
     * Process is a thin wrapper around proc_* functions to easily start independent PHP processes.
     *
     * @param string $command The command to run and its arguments
     * @param string|null $cwd The working directory or null to use the working dir of the current PHP process
     * @return Process
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     * @throws InvalidPathException
     * @see vendor/symfony/process/Process.php
     */
    private function process(string $command, string $cwd = null): Process
    {
        $cwd = $cwd ?? $this->workingTree;
        $env = null;
        $input = null;
        $timeout = 60;

        $process = new Process(explode(' ', $command), $cwd, $env, $input, $timeout);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                ExceptionHandler::prepare(new ProcessFailedException($process));
            }


        } catch (RuntimeException $e) {
            ExceptionHandler::prepare($e);
        }

        return $process;
    }

}
