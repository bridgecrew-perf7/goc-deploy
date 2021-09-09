<?php

namespace Marcth\GocDeploy\Repositories;


class ChangelogRepository extends Repository
{

    /**
     * @param string $changelogPath
     * @return array
     */
    public function readChangeLogFile(string $changelogPath): array
    {
        $file = fopen($changelogPath, "r");
        $lines = explode("\n", fread($file, 4096));
        fclose($file);

        $changeLog = [];

        foreach($lines as $line) {
            if(!trim($line)) {
                break;
            }

            $changeLog[] = $line;
        }

        return $changeLog;
    }

}
