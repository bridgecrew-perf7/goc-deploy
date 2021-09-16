<?php

namespace Marcth\GocDeploy\Traits;

use Marcth\GocDeploy\Entities\GitMetadata;
use Marcth\GocDeploy\Exceptions\ConnectionRefusedException;
use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\GitMergeConflictException;
use Marcth\GocDeploy\Exceptions\InvalidGitBranchException;
use Marcth\GocDeploy\Exceptions\InvalidGitReferenceException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\ChangelogRepository;
use Marcth\GocDeploy\Repositories\GitRepository;

trait TagCommandTrait
{
    protected $metadata;
    protected $changelog;
    protected $releaseTag;

    /**
     * Execute the console command.
     *
     * @param GitRepository $repository
     * @param ChangelogRepository $changelogRepository
     * @return void
     * @throws DirtyWorkingTreeException
     * @throws GitMergeConflictException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws ConnectionRefusedException
     */
    public function handle(GitRepository $repository, ChangelogRepository $changelogRepository)
    {
        $workingTree = $this->argument('working_tree') ?? config('goc-deploy.defaults.working_tree');
        $deployBranch = $this->argument('deploy_branch') ?? config('goc-deploy.defaults.deploy_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        if($this->option('clone')) {
            $url = $repository->getRemoteUrl($workingTree);

            $this->newLine();
            $this->line('Cloning "' . $url . '" to a temporary directory.');
            $this->line('This may take a few moments...');

            $workingTree = $repository->cloneToTemp($url);

            $this->info('The repository has been cloned to "' . $workingTree . '".');
            $this->newLine();
        }

        $this->metadata = GitMetadata::make($workingTree, $deployBranch, $mainBranch);
        $this->changelog = $changelogRepository->readChangeLogFile(config('goc-deploy.defaults.changelog_path'));

        $this->outputRepositorySummary($this->metadata)
             ->outputBranchVersionSummaries($this->metadata)
             ->outputChangelog($this->changelog);

        $this->releaseTag = $this->askForReleaseTag($this->metadata);

        if(!$this->confirmBranchMerge($repository, $this->metadata, $this->releaseTag, $this->changelog)) {
            $this->newLine();
            $this->warn('Aborted by user.');
            $this->newLine();
        }

       // dd($this->changelog);
       // dd($this->metadata);
    }

    /**
     * Outputs the git repository summary details to the console.
     *
     * @return $this
     */
    protected function outputRepositorySummary(GitMetadata $metadata): self
    {
        $this->newLine();
        $this->info('Repository Summary');
        $this->table([], [
            ['Repository Name', $metadata->name],
            ['Repository URL', $metadata->url],
            ['Working Tree', $metadata->workingTree],
            [
                'Deploy Branch (Tag)',
                ($metadata->deployBranch->name ?? null) . ' (' . ($metadata->deployBranch->tag ?? null) . ')'
            ],
            [
                'Main Branch (Tag)',
                ($metadata->mainBranch->name ?? null) . ' (' . ($metadata->mainBranch->tag ?? null) . ')'
            ],
        ]);

        return $this;
    }

    /**
     * Outputs the deployment and main branches' version summaries to the console.
     *
     * @return $this
     */
    protected function outputBranchVersionSummaries(GitMetadata $metadata): self
    {
        $this->newLine();
        $this->info('Branch Version Summaries');
        $this->table(['Branch', 'Major', 'Minor', 'Patch', 'Type', 'Descriptor', 'Revision'],
            [
                [
                    $metadata->deployBranch->name . ' (Deploy)',
                    $metadata->deployBranch->version->major,
                    $metadata->deployBranch->version->minor,
                    $metadata->deployBranch->version->patch,
                    $metadata->deployBranch->version->type,
                    $metadata->deployBranch->version->descriptor,
                    $metadata->deployBranch->version->revision,
                ], [
                    $metadata->mainBranch->name . ' (Main)',
                    $metadata->mainBranch->version->major,
                    $metadata->mainBranch->version->minor,
                    $metadata->mainBranch->version->patch,
                    $metadata->mainBranch->version->type,
                    $metadata->mainBranch->version->descriptor,
                    $metadata->mainBranch->version->revision,
                ]
            ]);

        $this->newLine();

        return $this;
    }

    /**
     * @param array $changelog
     * @return $this
     */
    protected function outputChangelog(array $changelog): self
    {
        $tableData = [];

        foreach($changelog as $line) {
            $tableData[][] = $line;
        }

        $this->info('Changelog for Release');
        $this->table([], $tableData);
        $this->newLine();

        return $this;
    }

    /**
     * @param GitMetadata $metadata
     * @return string
     */
    protected function askForReleaseTag(GitMetadata $metadata) : string
    {
        if (!$metadata->mainBranch->version->type) {
            $suggestion = $metadata->mainBranch->version->major . '.'
                . ($metadata->mainBranch->version->minor + 1) . '.'
                . $metadata->mainBranch->version->patch . '-rc.1';
        } else {
            $suggestion = $metadata->mainBranch->version->major . '.'
                . $metadata->mainBranch->version->minor . '.'
                . $metadata->mainBranch->version->patch . '-rc.'
                . ($metadata->mainBranch->version->revision + 1);
        }

        $question = 'Please enter the tag reference for this release to staging:';

        return $this->ask(sprintf($question, $metadata->mainBranch->name), $suggestion);
    }

    /**
     * @param GitRepository $repository
     * @param GitMetadata $metadata
     * @param string $releaseTag
     * @param array $changelogMessage
     * @return bool
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    protected function confirmBranchMerge(
        GitRepository $repository,
        GitMetadata $metadata,
        string $releaseTag,
        array $changelogMessage): bool
    {
        $question = 'Do you want to merge "%s" into "%s" and reference it with tag "%s" using the changelog above?';
        $question = sprintf($question, $metadata->deployBranch->name, $metadata->mainBranch->name, $releaseTag);

        if($this->confirm($question)) {
            $repository->checkoutBranch($metadata->deployBranch->name, $metadata->workingTree);
            $repository->pullRemote($metadata->workingTree);

            $repository->checkoutBranch($metadata->mainBranch->name, $metadata->workingTree);
            $repository->pullRemote($metadata->workingTree);

            try {
                $repository->mergeBranch($metadata->deployBranch->name, $metadata->workingTree);
                $repository->tagBranch($releaseTag, $metadata->workingTree, implode("\n", $changelogMessage));

                $repository->pushToRemote($metadata->workingTree);
                $repository->pushTagsToRemote($metadata->workingTree);

                $repository->checkoutBranch($metadata->deployBranch->name, $metadata->workingTree);
                $repository->pullRemote($metadata->workingTree);

                $repository->mergeBranch($metadata->mainBranch->name, $metadata->workingTree);
                $repository->pushToRemote($metadata->workingTree);
            } catch(GitMergeConflictException $e) {
                $this->warn('Aborting git merge...');

                $repository->abortMergeBranch($metadata->workingTree);

                throw $e;
            }
        }

        return true;
    }
}
