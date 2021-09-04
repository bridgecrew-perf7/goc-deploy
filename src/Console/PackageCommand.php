<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Carbon;

/**
 * Requires git
 * Requires Linux (mktemp -d)
 */
class PackageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goc-deploy:package
                            {working_tree?  : The path to the local working repository.}
                            {main_branch?   : The name of the main/master branch or branch to tag.}
                            ';

    /**
     * The console command description.
     *
     * @var string
     * @todo I am Tag!
     */
    protected $description = 'I am Package!';


    /**
     * @var string[]
     * @see https://www.youtube.com/watch?v=aJfgHSUlr-s - hakety hak - The Coasters (don't look back!)
     */
    protected $repoMetadata = [
        'name' => null,
        'url' => null,
        'workingTree' => null,
        'main_branch' => null,
        'main_branch_tag' => null,
        'package_filename' => null,
        'initial_state' => [
            'branch' => null,
        ],
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $workingTree = $this->argument('working_tree') ?? config('goc-deploy.defaults.working_tree');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $this->validate($workingTree, $mainBranch)
            ->gatherMetadata($workingTree, $mainBranch)
            ->gatherTags()
            ->generatePackageFilename();

        dd($this->repoMetadata);
    }

    /**
     * Ensures the supplied local path is at the root of a git repository with no uncommitted/untracked changes
     * from the current HEAD. Oh, and something about branches existing.
     *
     * @param string $workingTree
     * @param string $mainBranch
     * @return $this
     */
    protected function validate(string $workingTree, string $mainBranch): self
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

            if ($process->getOutput()) {
                $this->newLine(1);
                $this->error(" ERROR: Resolve the uncommitted/untracked changes in your working branch.");
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
        try {
            $this->process(['git', 'rev-parse', '--verify', $mainBranch], $workingTree)->getOutput();
        } catch (ProcessFailedException $exception) {
            $this->newLine(1);
            $this->error(" ERROR: The '$mainBranch' does not exist in the repository in '$workingTree'.");
            $this->newLine(1);

            exit(1);
        }

        return $this;
    }

    /**
     * @param string $workingTree
     * @param string $mainBranch
     * @return $this
     * @throws ProcessFailedException
     */
    protected function gatherMetadata(string $workingTree, string $mainBranch): self
    {
        $url = trim($this->process(['git', 'config', '--get', 'remote.origin.url'], $workingTree)->getOutput());
        $name = $this->repoMetadata['url'] = basename($url, ".git");

        $repositoryHead = trim($this->process(['git', 'symbolic-ref', 'HEAD'], $workingTree)->getOutput());
        $initialBranch = substr($repositoryHead, strrpos($repositoryHead, '/') + 1);

        $this->repoMetadata['name'] = $name;
        $this->repoMetadata['url'] = $url;
        $this->repoMetadata['workingTree'] = $tmpWorkingTree ?? $workingTree;
        $this->repoMetadata['main_branch'] = $mainBranch;
        $this->repoMetadata['initial_state']['branch'] = $initialBranch;

        return $this;
    }

    /**
     * @return $this
     */
    protected function gatherTags(): self
    {
        $this->checkoutBranch($this->repoMetadata['main_branch']);
        $process = $this->process(['git', 'describe'], $this->repoMetadata['workingTree']);
        $this->repoMetadata['main_branch_tag'] = trim($process->getOutput());

        return $this;
    }

    protected function generatePackageFilename(): self
    {
        $this->repoMetadata['package_filename'] = sprintf('%s_%s_%s',
            $this->repoMetadata['name'],
            $this->repoMetadata['main_branch_tag']

        );
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
                    $this->repoMetadata['main_branch'] . ' (Main)',
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
    protected function askForReleaseTag(): self
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
    protected function outputChangelog(): self
    {
        $tableData = [];

        foreach ($this->repoMetadata['changelogEntries'] as $line) {
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

        if ($this->confirm($question)) {
            $this->checkoutBranch($this->repoMetadata['deploy_branch']);
            $this->process(['git', 'pull'], $this->repoMetadata['workingTree']);

            $this->checkoutBranch($this->repoMetadata['main_branch']);
            $this->process(['git', 'pull'], $this->repoMetadata['workingTree']);

            $process = $this->process(['git', 'merge', '--no-ff', '--no-edit', $this->repoMetadata['deploy_branch']],
                $this->repoMetadata['workingTree']);

            if ($process->getExitCode() != 0) {
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
