<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\InvalidPathException;

class ChangelogRepository extends Repository
{

    /**
     * @param string $changelogPath
     * @return array
     */
    public function readChangeLogFile(string $changelogPath): array
    {
        if(!is_readable($changelogPath)) {
            $message = 'ERROR: The changelog file "%s" does not exist or is not readable.';

            throw new InvalidPathException(sprintf($message, $changelogPath));
        }

        $file = fopen($changelogPath, "r");
        $lines = explode("\n", fread($file, 4096));
        fclose($file);

        $changeLog = [];

        foreach ($lines as $line) {
            if (!trim($line)) {
                break;
            }

            $changeLog[] = $line;
        }

        return $changeLog;
    }

}
