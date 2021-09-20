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

class Repository extends BaseRepository
{
    /**
     * Clones the remote repository to the specified $directory.
     *
     * @param string $url
     * @param string $directory
     * @return $this
     * @throws ProcessException
     */
    public function clone(string $url, string $directory): self
    {
        $this->process('git clone ' . $url, $directory);

        return $this;
    }

    /**
     * Issues the `rm -fr` command targetting the specified path.
     *
     * @param string $path
     * @return $this
     * @throws ProcessException
     */
    public function delete(string $path): self
    {
        $this->process('rm -fr ' . $path, getcwd());

        return $this;
    }

    /**
     * Returns the (by default, absolute) path of the top-level directory of the working tree.
     *
     * @param string $workingTree
     * @return string
     * @throws ProcessException
     */
    public function getLocalRootPath(string $workingTree): string
    {
        return $this->execute('git rev-parse --show-toplevel', $workingTree);
    }

    /**
     * Executes the 'git config --get remote.origin.url' to get the remote git repository URL from the specified local
     * working tree.
     *
     * @param string $workingTree
     * @return string
     * @throws ProcessException
     */
    public function getRemoteUrl(string $workingTree): string
    {
        return $this->execute('git config --get remote.origin.url', $workingTree);
    }

    /**
     * Create the director(ies), if they do not already exist.
     *
     * @param string $directory
     * @return string
     * @throws ProcessException
     */
    public function makeDirectories(string $directory): string
    {
        $this->process('mkdir -p ' . $directory, base_path());

        return realpath($directory);
    }

    /**
     * Executes the `git fetch origin` command.
     *
     * @param string $workingTree
     * @return $this
     * @throws ProcessException
     */
    public function refreshOriginMetadata(string $workingTree): self
    {
        $this->process('git fetch origin', $workingTree);

        return $this;
    }

    /**
     * Ensures there are no differences between the specified working tree and remote repository (including untacked
     * files).
     *
     * @param string $workingTree
     * @return $this
     * @throws DirtyWorkingTreeException
     * @throws ProcessException
     */
    public function validateWorkingTree(string $workingTree): self
    {
        $process = $this->process('git status --porcelain', $workingTree);

        if (trim($process->getOutput())) {
            throw new DirtyWorkingTreeException();
        }

        return $this;
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
        $this->process(['git', 'tag', '-f', '-a', $releaseTag, '-m', $changelogMessage], $workingTree);

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

        if ($currentBranch != $branch) {
            $this->checkoutBranch($branch, $workingTree);
        }

        $tag = $this->execute('git describe', $workingTree);

        if ($currentBranch != $branch) {
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
            if ($revision && !is_int($revision)) {
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
     * @param string $branch
     * @param string $workingTree
     * @return GitBaseRepository
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

    public function package(string $workingTree)
    {
        $output = $this->execute('composer install --optimize-autoloader --no-dev', $workingTree);
        print $output . "\n";
//
//        $output = $this->execute('bower install', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('npm install', $workingTree);
//        print $output . "\n";
//
//        $output = $this->execute('npm run production', $workingTree);
//        print $output . "\n";


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
            '--directory=' . $workingTree,
            '-zvcf',
            'passport-src_test.tar.gz',
            '.',
        ]), '/var/www');

        print $output . "\n";

        $output = $this->execute('composer install', $workingTree);
        print $output . "\n";
    }

    /**
     * @param string $poFilename
     * @return bool
     *
     * @throws CompileTranslationException
     * @throws \Marcth\GocDeploy\Exceptions\ConnectionRefusedException
     * @throws \Marcth\GocDeploy\Exceptions\GitMergeConflictException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidGitBranchException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidGitReferenceException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidPathException
     */
    public function compileMessageCatalog(string $poFilename): bool {

        $moFilename = str_replace('.po', '.mo', $poFilename);

        try {
            $this->process(implode(' ', ['msgfmt', $poFilename, '-o', $moFilename]), getcwd());
        } catch(ProcessException $e) {
            throw new CompileTranslationException($e->getMessage(), $e->getCode());
        }

        return true;
    }

    /**
     * Returns an array of each line read from the first $length bytes of the specified $filename.
     *
     * @param string $filename
     * @param int $length Defaults to 4096
     * @return array|null
     * @link https://www.php.net/manual/en/function.fread.php
     */
    public function readFile(string $filename, int $length=4096): ?array
    {
        if (!is_readable($filename)) {
            $message = 'ERROR: The changelog file "%s" does not exist or is not readable.';

            throw new InvalidPathException(sprintf($message, $filename));
        }

        $file = fopen($filename, "r");
        $lines = explode("\n", fread($file, $length));
        fclose($file);

        return $lines;
    }

    /**
     * The install command reads the composer. lock file from the current directory, processes it, and downloads and installs all the libraries and dependencies outlined in that file.
     * @param string $workingTree
     * @param bool $includeDevPackages
     * @return $this
     *
     * @throws ProcessException
     * @throws \Marcth\GocDeploy\Exceptions\ConnectionRefusedException
     * @throws \Marcth\GocDeploy\Exceptions\GitMergeConflictException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidGitBranchException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidGitReferenceException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException
     * @throws \Marcth\GocDeploy\Exceptions\InvalidPathException
     */
    public function composerInstall(string $workingTree, bool $includeDevPackages): self
    {
        $command = 'composer install ';
        $command .= !$includeDevPackages ? '--no-dev --no-autoloader --no-scripts --no-progress' : '';

        exec('cd ' . $workingTree . ' && '. $command, $output, $code);
        print $code . ":" . $output;
        dd(__METHOD__);
        return $this;
    }
}

