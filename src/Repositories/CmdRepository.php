<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\CompileTranslationException;
use Marcth\GocDeploy\Exceptions\ProcessException;

class CmdRepository extends Repository
{

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
/*
 *         $output = $this->execute('composer install --optimize-autoloader --no-dev', $workingTree);
        print $output . "\n";
 */
}
