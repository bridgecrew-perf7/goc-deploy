<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\ConnectionRefusedException;
use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\GitMergeConflictException;
use Marcth\GocDeploy\Exceptions\InvalidGitBranchException;
use Marcth\GocDeploy\Exceptions\InvalidGitReferenceException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;

class GitRepository extends Repository
{

    /**
     * @param string $workingTree
     * @return string
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getLocalRootPath(string $workingTree): string
    {
        return $this->execute('git rev-parse --show-toplevel', $workingTree);
    }

    /**
     * @param string $workingTree
     * @return string
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
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
     * @throws ConnectionRefusedException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function cloneToTemp(string $url): string
    {
        $directory = $this->execute('mktemp -d', base_path());
        $this->process('git clone ' . $url, $directory);

        return  $directory . DIRECTORY_SEPARATOR . $this->parseNameFromUrl($url);
    }

    /**
     * @param string $url
     * @param string $directory
     * @return GitRepository
     * @throws ConnectionRefusedException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function clone(string $url, string $directory): self
    {
        $this->process('git clone ' . $url, $directory);

        return  $this;
    }



    /**
     * @param string $workingTree
     * @return GitRepository
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    public function refreshOriginMetadata(string $workingTree): self
    {
        $this->process('git fetch origin', $workingTree);

        return $this;
    }

    /**
     * @param string $workingTree
     * @return $this
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    public function pullRemote(string $workingTree): self
    {
        $this->process('git pull', $workingTree);

        return $this;
    }

    /**
     * @param string $deployBranch
     * @param string $workingTree
     * @return $this
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    public function mergeBranch(string $deployBranch, string $workingTree): self
    {
        $this->process('git merge --no-ff --no-edit ' . $deployBranch, $workingTree);

        return $this;
    }

    /**
     * @param string $workingTree
     * @return $this
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    public function abortMergeBranch(string $workingTree): self
    {
        $this->process('git merge --abort', $workingTree);

        return $this;
    }

    /**
     * @param string $workingTree
     * @return $this
     * @throws ConnectionRefusedException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function pushToRemote(string $workingTree): self
    {
        $this->process('git push', $workingTree);

        return $this;
    }

    /**
     * @param string $workingTree
     * @return $this
     * @throws ConnectionRefusedException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function pushTagsToRemote(string $workingTree): self
    {
        $this->process('git push --tags -f', $workingTree);

        return $this;
    }

    /**
     * @param string $releaseTag
     * @param string $workingTree
     * @param string $changelogMessage
     * @return $this
     * @throws ConnectionRefusedException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function tagBranch(string $releaseTag, string $workingTree, string $changelogMessage): self
    {
        $this->process(['git', 'tag' , '-f',  '-a', $releaseTag, '-m', $changelogMessage], $workingTree);

        return $this;
    }

    /**
     * @param string $workingTree
     * @return string
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getCurrentBranch(string $workingTree): string
    {
        return basename($this->execute('git symbolic-ref -q HEAD', $workingTree));
    }

    /**
     * @param string $branch
     * @param string $workingTree
     * @return string
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
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
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
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
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    public function checkoutBranch(string $branch, string $workingTree): self
    {
        $this->process('git -c advice.detachedHead=false checkout --quiet ' . $branch, $workingTree);

        return $this;
    }


    public function package(string $branch, string $workingTree) {
//        $output = $this->execute('msgfmt ./resources/i18n/en_CA/LC_MESSAGES/messages.po -o ./resources/i18n/en_CA/LC_MESSAGES/messages.mo', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('msgfmt ./resources/i18n/fr_CA/LC_MESSAGES/messages.po -o ./resources/i18n/fr_CA/LC_MESSAGES/messages.mo', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('composer install --optimize-autoloader --no-dev', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('bower install', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('npm install', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('npm run production', $workingTree);
//        print $output . "\n";

        $workingTree = "/var/www/";

        $output = $this->execute(implode(' ', [
            'tar',
            '--exclude-vcs',
            '--exclude=bower_components',
            '--exclude=node_modules',
            '--exclude=storage',
            '--exclude=tests',
            '--exclude=.idea',
            '--exclude=.editorconfig',
            '--exclude=.env',
            '--exclude=scripts',
            '--exclude=bootstrap/cache/*',
            '--directory=/var/www/passport/src',
            '-zvcf',
            'passport-src_test.tar.gz',
            '.',
            ]), $workingTree);

        print $output . "\n";



    }
}
