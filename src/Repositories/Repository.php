<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\GitMergeConflictException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;

class Repository extends BaseRepository
{

    /**
     * @param string $workingTree
     * @param string $tarball
     * @return string
     * @throws ProcessException
     */
    public function package(string $workingTree, string $tarball): string
    {

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
            $tarball,
            '.',
        ]), '/var/www');

        return $output;
    }


    /**
     * Attempts to abort the current conflict resolution process and reconstruct the pre-merge state.
     *
     * @param string $workingTree
     * @return $this
     * @throws ProcessException
     */
    public function abortMergeBranch(string $workingTree): self
    {
        $this->process('git merge --abort', $workingTree);

        return $this;
    }

    /**
     * Switches branches or restore working tree files.
     *
     * @param string $branch
     * @param string $workingTree
     * @return $this
     * @throws ProcessException
     */
    public function checkoutBranch(string $branch, string $workingTree): self
    {
        $this->process('git -c advice.detachedHead=false checkout --quiet ' . $branch, $workingTree);

        return $this;
    }

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
     * Generates binary message catalog from textual translation description.
     *
     * @param string $poFilename
     * @return $this
     */
    public function compileMessageCatalog(string $poFilename): self {

        $moFilename = str_replace('.po', '.mo', $poFilename);

        try {
            $this->process(implode(' ', ['msgfmt', $poFilename, '-o', $moFilename]), getcwd());
        } catch(ProcessException $e) {
            throw new CompileTranslationException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Downloads and installs all the libraries and dependencies outlined in the composer.json file.
     *
     * @param string $composerPath
     * @param bool $includeDevPackages
     * @return bool
     */
    public function composerUpdate(string $composerPath, bool $includeDevPackages): bool
    {
        $command = 'composer update ';
        $command .= !$includeDevPackages ? '--no-dev --no-autoloader --no-scripts --no-progress' : '';

        exec('cd ' . $composerPath . ' && '. $command, $output, $code);

        return !$code;
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
     * Returns the name of the current branch in the specified working tree.
     *
     * @param string $workingTree
     * @return string
     * @throws ProcessException
     */
    public function getCurrentBranch(string $workingTree): string
    {
        return basename($this->execute('git symbolic-ref -q HEAD', $workingTree));
    }

    /**
     * Returns the referenced tag of the current branch in the specified working tree.
     *
     * @param string $branch
     * @param string $workingTree
     * @return string
     * @throws ProcessException
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
     * Issues a `git merge` to join the development history of the specified $mergeBranch into the current working
     * branch.
     *
     * @param string $mergeBranch
     * @param string $workingTree
     * @return $this
     * @throws GitMergeConflictException
     * @throws ProcessException
     */
    public function mergeBranch(string $mergeBranch, string $workingTree): self
    {
        try {
            $this->process('git merge --no-ff --no-edit ' . $mergeBranch, $workingTree);
        } catch (ProcessFailedException $e) {
            throw new GitMergeConflictException(null, null, $e);
        }

        return $this;
    }

    /**
     * @param string $packageJsonPath
     * @return string
     * @todo  No error handling
     */
    public function npmInstall(string $packageJsonPath): bool
    {
        $command = 'cd ' . $packageJsonPath . ' && npm install';
        exec($command, $output, $code);

        return !$code;
    }

    /**
     * @param string $packageJsonPath
     * @param bool $productionEnv
     * @return bool
     * @todo  No error handling
     */
    public function npmRun(string $packageJsonPath, bool $productionEnv=false): string
    {
        $command = $productionEnv ? 'npm run development' : 'npm run production';
        $command = 'cd ' . $packageJsonPath . ' && ' . $command;

        exec($command, $output, $code);

        return !$code;
    }

    /**
     * Returns the directory name of the git URL.
     *
     * @param string $url
     * @return string
     */
    public function parseNameFromUrl(string $url): string
    {
        return basename($url, '.git');
    }

    /**
     * Attempts to parse the semantic version details and metadata from the specified $releaseTag.
     *
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
     * Issues the `git pull` command on the specified working tree.
     *
     * @param string $workingTree
     * @return $this
     * @throws ProcessException
     */
    public function pullFromRemote(string $workingTree): self
    {
        $this->process('git pull', $workingTree);

        return $this;
    }

    /**
     * Issues the `git push` command and optionally a `git push --tags` when $includeTags is true.
     *
     * @param string $workingTree
     * @param bool $includeTags
     * @return $this
     * @throws ProcessException
     */
    public function pushToRemote(string $workingTree, bool $includeTags=false): self
    {
        $this->process('git push', $workingTree);

        if($includeTags) {
            $this->process('git push --tags', $workingTree);
        }

        return $this;
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
     * Tags the current branch in the $workingTree with the specified tag and changelog message.
     *
     * @param string $releaseTag
     * @param string $workingTree
     * @param string $changelogMessage
     * @return $this
     * @throws ProcessException
     */
    public function tagBranch(string $releaseTag, string $workingTree, string $changelogMessage): self
    {
        $this->process(['git', 'tag', '-f', '-a', $releaseTag, '-m', $changelogMessage], $workingTree);

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
}
