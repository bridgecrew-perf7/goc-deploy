<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\CompileTranslationException;
use Marcth\GocDeploy\Exceptions\ProcessException;

class CmdRepository extends Repository
{

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
        $lines = explode("\n", fread($file, 4096));
        fclose($file);

        return $lines;
    }

}
