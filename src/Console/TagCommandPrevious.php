<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Requires git
 * Requires Linux (mktemp -d)
 */
class TagCommandPrevious extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goc-deploy:tag
                            {deploy_branch? : The name of the branch to deploy.}
                            {working_tree?  : The path to the local working repository.}
                            {main_branch?   : The name of the main/master branch or branch to tag.}
                            {--C|clone      : Clone a working repository into a new temporary directory.}';

    /**
     * The console command description.
     *
     * @var string
     * @todo I am Tag!
     */
    protected $description = 'I am Tag!';

    /**
     * @var string[]
     * @see https://www.youtube.com/watch?v=aJfgHSUlr-s - hakety hak - The Coasters (don't look back!)
     */
    protected $repoMetadata = [
        'name' => null,
        'url' => null,
        'workingTree' => null,
        'deploy_branch' => null,
        'deploy_branch_tag' => null,
        'main_branch' => null,
        'main_branch_tag' => null,
        'initial_state' => [
            'workingTree' => null,
            'branch' => null,
        ],
        'deploy_branch_versions' => [
            'major' => null,
            'minor' => null,
            'patch' => null,
            'type' => null,
            'descriptor' => null,
            'revision' => null,
        ],
        'main_branch_versions' => [
            'major' => null,
            'minor' => null,
            'patch' => null,
            'type' => null,
            'descriptor' => null,
            'revision' => null,
        ],
        'changelogEntries' => null,
        'release_tag' => null,
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $workingTree = $this->argument('working_tree') ?? config('goc-deploy.defaults.working_tree');
        $deployBranch = $this->argument('deploy_branch') ?? config('goc-deploy.defaults.deploy_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $this
            //->validate($workingTree, $deployBranch, $mainBranch)
      //      ->gatherMetadata($workingTree, $deployBranch, $mainBranch)
         //   ->cloneIfRequired()
            ->gatherTags()
            ->readChangeLogFile()
            ->outputRepositorySummary()
            ->askForReleaseTag()
            ->outputChangelog()
            ->confirmBranchMerge();

        // Did not push merged commit or push --tags
        //dd($this->repoMetadata);
    }

    /**
     * Ensures the supplied local path is at the root of a git repository with no uncommitted/untracked changes
     * from the current HEAD. Oh, and something about branches existing.
     *
     * @param string $workingTree
     * @param string $deployBranch
     * @param string $mainBranch
     * @return $this
     */
    protected function validate(string $workingTree, string $deployBranch, string $mainBranch): self
    {
        // Ensure the path exists.
        if (!is_dir($workingTree)) {
            $this->newLine(1);
            $this->error(" ERROR: The local repository directory '$workingTree' is not valid.");
            $this->newLine(1);

            exit(1);
        }

        try { // Ensure the path reaches a git repository.
            // Ensure the path points to the root of the local git repository.
            $process = $this->process(['git', 'rev-parse', '--show-toplevel'], $workingTree);

            if ($workingTree != trim($process->getOutput())) {
                $this->newLine(1);
                $this->error(" ERROR: The path '$workingTree' is not the root of the working repository.");
                $this->newLine(1);

                exit(1);
            }

            // Fetch branches and/or tags and ensure remote repository is accessible.
            $this->process(['git', 'fetch', 'origin'], $workingTree);

            // Ensure the working tree matches the current HEAD commit
            $process = $this->process(['git', 'status', '--porcelain'], $workingTree);

            if (!$this->option('clone') && $process->getOutput()) {
                $this->newLine(1);
                $this->error(" ERROR: Resolve the uncommitted/untracked changes in your working branch.");
                $this->newLine(1);
                $this->line(" Hint: The '--clone' option can be used to bypass this issue.\n");
                $this->newLine(1);

                exit(1);
            }
        } catch (ProcessFailedException $exception) {
            $this->newLine(1);
            $this->error(" ERROR: Could not find a remote git repository from path '$workingTree'.");
            $this->newLine(1);

            exit(1);
        }

        // Ensure the branches involved in the deployment exist.
        foreach ([$deployBranch, $mainBranch] as $branch) {
            try {
                $this->process(['git', 'rev-parse', '--verify', $branch], $workingTree)->getOutput();
            } catch (ProcessFailedException $exception) {
                $this->newLine(1);
                $this->error(" ERROR: The '$branch' does not exist in the repository in '$workingTree'.");
                $this->newLine(1);

                exit(1);
            }
        }

        return $this;
    }

    /**
     * @param string $workingTree
     * @param string $deployBranch
     * @param string $mainBranch
     * @return $this
     * @throws ProcessFailedException
     */
    protected function gatherMetadata(string $workingTree, string $deployBranch, string $mainBranch): self
    {
        $url = trim($this->process(['git', 'config', '--get', 'remote.origin.url'], $workingTree)->getOutput());
        $name = $this->repoMetadata['url'] = basename($url, ".git");

        $repositoryHead = trim($this->process(['git', 'symbolic-ref', 'HEAD'], $workingTree)->getOutput());
        $initialBranch = substr($repositoryHead, strrpos($repositoryHead, '/') + 1);

        $this->repoMetadata['name'] = $name;
        $this->repoMetadata['url'] = $url;
        $this->repoMetadata['workingTree'] = $tmpWorkingTree ?? $workingTree;
        $this->repoMetadata['deploy_branch'] = $deployBranch;
        $this->repoMetadata['main_branch'] = $mainBranch;
        $this->repoMetadata['initial_state']['workingTree'] = $workingTree;
        $this->repoMetadata['initial_state']['branch'] = $initialBranch;

        return $this;
    }

    /**
     * @return $this
     * @throws ProcessFailedException
     */
    protected function cloneIfRequired(): self
    {
        if ($this->option('clone')) {
            $workingTree = trim($this->process(['mktemp', '-d'])->getOutput());

            $this->newLine(1);
            $this->line("Cloning `" . $this->repoMetadata['name'] . "` to '" . $workingTree . "'.");
            $this->line('This may take a few moments...');

            $this->process(['git', 'clone', $this->repoMetadata['url']], $workingTree);

            $this->repoMetadata['workingTree'] = $workingTree . DIRECTORY_SEPARATOR . $this->repoMetadata['name'];
        }

        return $this;
    }

    /**
     * Parses the supplied $releaseTag string into an array of Version details.
     *
     * Examples of release tag versions:
     *   4.30.0-jira-1592.3
     *   4.31.0-rc.1
     *   4.30.0
     * @param string $releaseTag
     * @return array
     */
    protected function parseReleaseTag(string $releaseTag): array
    {/*
        $pos = strpos($releaseTag, '-');
        $version = substr($releaseTag, 0, $pos ? $pos : strlen($releaseTag));
        $versionParts = explode('.', $version);
        $metadata = $pos ? substr($releaseTag, $pos + 1) : null;

        if ($metadata) {
            $pos = strrpos($metadata, '.');
            $revision = substr($metadata, $pos + 1);
            $metadata = substr($metadata, 0, $pos);

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
*/
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
     * @return $this
     */
    protected function gatherTags(): self
    {
        $this->checkoutBranch($this->repoMetadata['deploy_branch']);
        $process = $this->process(['git', 'describe'], $this->repoMetadata['workingTree']);
        $this->repoMetadata['deploy_branch_tag'] = trim($process->getOutput());
        $this->repoMetadata['deploy_branch_versions'] = $this->parseReleaseTag($this->repoMetadata['deploy_branch_tag']);

        $this->checkoutBranch($this->repoMetadata['main_branch']);
        $process = $this->process(['git', 'describe'], $this->repoMetadata['workingTree']);
        $this->repoMetadata['main_branch_tag'] = trim($process->getOutput());
        $this->repoMetadata['main_branch_versions'] = $this->parseReleaseTag($this->repoMetadata['main_branch_tag']);

        return $this;
    }

    /**
     * @return $this
     */
    public function readChangeLogFile(): self
    {
        $file = fopen(config('goc-deploy.defaults.changelog_path'), "r");
        $lines = explode("\n", fread($file, 4096));
        fclose($file);

        foreach($lines as $line) {
            if(!trim($line)) {
                break;
            }

            $this->repoMetadata['changelogEntries'][] = $line;
        }

        return $this;
    }




    /**
     * Outputs a repository summary details to the console.
     *
     * @return $this
     */
    protected function outputRepositorySummary(): self
    {
        $this->newLine(1);
        $this->info('Repository Summary');
        $this->table([], [
            ['Repository Name', $this->repoMetadata['name']],
            ['Repository URL', $this->repoMetadata['url']],
            ['Working Tree', $this->repoMetadata['workingTree']],
            ['Deploy Branch', $this->repoMetadata['deploy_branch']],
            ['Deploy Branch Tag', $this->repoMetadata['deploy_branch_tag']],
            ['Main Branch', $this->repoMetadata['main_branch']],
            ['Main Branch Tag', $this->repoMetadata['main_branch_tag']],
        ]);

        if ($this->repoMetadata['deploy_branch'] == 'develop') {
            $this->newLine(1);
            $this->info('Branch Version Summaries');
            $this->table(['Branch', 'Major', 'Minor', 'Patch', 'Type', 'Descriptor', 'Revision'],
                [
                    [
                        $this->repoMetadata['deploy_branch'] . ' (Deploy)',
                        $this->repoMetadata['deploy_branch_versions']['major'],
                        $this->repoMetadata['deploy_branch_versions']['minor'],
                        $this->repoMetadata['deploy_branch_versions']['patch'],
                        $this->repoMetadata['deploy_branch_versions']['type'],
                        $this->repoMetadata['deploy_branch_versions']['descriptor'],
                        $this->repoMetadata['deploy_branch_versions']['revision'],
                    ], [
                        $this->repoMetadata['main_branch']  . ' (Main)',
                        $this->repoMetadata['main_branch_versions']['major'],
                        $this->repoMetadata['main_branch_versions']['minor'],
                        $this->repoMetadata['main_branch_versions']['patch'],
                        $this->repoMetadata['main_branch_versions']['type'],
                        $this->repoMetadata['main_branch_versions']['descriptor'],
                        $this->repoMetadata['main_branch_versions']['revision'],
                    ]
                ]);
        } else {
            $this->newLine(1);
            $this->error(__METHOD__ . ':' . __LINE__ . '() use case not implemented yet.');
            $this->newLine(1);

            exit(1);
        }

        $this->newLine(1);

        return $this;
    }

    /**
     * @return $this
     */
    protected function askForReleaseTag() : self
    {
        if (!$this->repoMetadata['main_branch_versions']['type']) {
            $suggestion = $this->repoMetadata['main_branch_versions']['major'] . '.'
                . ($this->repoMetadata['main_branch_versions']['minor'] + 1) . '.'
                . $this->repoMetadata['main_branch_versions']['patch'] . '-rc.1';
        } else {
            $suggestion = $this->repoMetadata['main_branch_versions']['major'] . '.'
                . $this->repoMetadata['main_branch_versions']['minor'] . '.'
                . $this->repoMetadata['main_branch_versions']['patch'] . '-rc.'
                . ($this->repoMetadata['main_branch_versions']['revision'] + 1);
        }

        $question = sprintf('Please enter the tag reference for this release to staging:',
            $this->repoMetadata['main_branch']);

        $answer = $this->repoMetadata['release_tag'] = $this->ask($question, $suggestion);

        return $this;
    }


    /**
     * @return $this
     */
    protected function outputChangelog(): self{
        $tableData = [];

        foreach($this->repoMetadata['changelogEntries'] as $line) {
            $tableData[][] = $line;
        }

        $this->info(sprintf('Changelog for Release "%s"', $this->repoMetadata['release_tag']));
        $this->table([], $tableData);

        return $this;
    }

    /**
     * @return $this
     */
    protected function confirmBranchMerge(): self
    {
        $question = sprintf('Do you want to merge "%s" into "%s" and reference the commit with tag "%s"'
            . ' using the changelog above?',
            $this->repoMetadata['deploy_branch'],
            $this->repoMetadata['main_branch'],
            $this->repoMetadata['release_tag']);

        if($this->confirm($question)) {
            $this->checkoutBranch($this->repoMetadata['deploy_branch']);
            $this->process(['git', 'pull'], $this->repoMetadata['workingTree']);

            $this->checkoutBranch($this->repoMetadata['main_branch']);
            $this->process(['git', 'pull'], $this->repoMetadata['workingTree']);

            $process = $this->process(['git', 'merge', '--no-ff', '--no-edit', $this->repoMetadata['deploy_branch']],
                $this->repoMetadata['workingTree']);

            if($process->getExitCode() != 0) {
                $this->newLine(1);
                $this->error('A conflict occurred when attempting to merge the repository.');
                $this->newLine(1);
                $this->line('Aborting git merge.');

                $process = $this->process(['git', 'merge', '--abort', $this->repoMetadata['deploy_branch']],
                    $this->repoMetadata['workingTree']);

                exit(1);
            } else {
                $process = $this->process(['git', 'tag', '-f', '-a', $this->repoMetadata['release_tag'], '-m',
                    implode("\n", $this->repoMetadata['changelogEntries'])],
                    $this->repoMetadata['workingTree']);
            }
        } else {
            $this->newLine(1);
            $this->warn('Aborted.');
        };

        $this->newLine(1);

        $this->checkoutBranch($this->repoMetadata['initial_state']['branch']);

        return $this;
    }

    /**
     * Issues a git command to check out the specified $branch in the current working tree.
     *
     * @param string $branch
     */
    private function checkoutBranch(string $branch)
    {
        $this->process(['git', '-c', 'advice.detachedHead=false', 'checkout', '--quiet', $branch],
            $this->repoMetadata['workingTree']);
    }

    /**
     * Process is a thin wrapper around proc_* functions to easily start independent PHP processes.
     *
     * @param array $command The command to run and its arguments listed as separate entries
     * @param string|null $cwd The working directory or null to use the working dir of the current PHP process
     * @param array|null $env The environment variables or null to use the same environment as the current PHP process
     * @param mixed $input The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @throws LogicException When proc_open is not installed
     * @throws ProcessFailedException
     *
     * @see vendor/symfony/process/Process.php
     */
    private function process(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60): Process
    {
        $process = new Process($command, $cwd, $env, $input, $timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

}
