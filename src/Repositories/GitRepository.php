<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;

class GitRepository extends Repository
{

    /**
     * @param string $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     * @throws InvalidPathException
     */
    public function getLocalRootPath(string $workingTree): string
    {
        return $this->execute('git rev-parse --show-toplevel', $workingTree);
    }

    /**
     * @param string $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getRemoteUrl(string $workingTree): string
    {
        return $this->execute('git config --get remote.origin.url', $workingTree);
    }

    /**
     * @param string $url
     * @return string
     */
    public function parseNameFromUrl(string $url): string
    {
        return basename($url, '.git');
    }

    /**
     * @param string $url
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function cloneToTemp(string $url): string
    {
        $directory = $this->execute('mktemp -d');
        $this->process('git clone ' . $url, $directory);

        return  $directory . DIRECTORY_SEPARATOR . $this->parseNameFromUrl($url);
    }

    /**
     * @param string $workingTree
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function refreshOriginMetadata(string $workingTree)
    {
        $this->execute('git fetch origin', $workingTree);
    }

    /**
     * @param string $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getCurrentBranch(string $workingTree): string
    {
        return basename($this->execute('git symbolic-ref -q HEAD', $workingTree));
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
     * @param string $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getCurrentTag(string $branch, string $workingTree): string
    {
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
     * @param string $workingTree
     * @return GitRepository
     * @throws DirtyWorkingTreeException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function validateWorkingTree(string $workingTree): self
    {
        $process = $this->process('git status --porcelain', $workingTree);

        if(trim($process->getOutput())) {
            throw new DirtyWorkingTreeException();
        }

        return $this;
    }

    /**
     * @param string $branch
     * @param string $workingTree
     * @return GitRepository
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function checkoutBranch(string $branch, string $workingTree): self
    {
        $this->process('git -c advice.detachedHead=false checkout --quiet ' . $branch, $workingTree);

        return $this;
    }
}
