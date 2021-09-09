<?php

namespace Marcth\GocDeploy\Traits;

use Marcth\GocDeploy\Entities\GitMetadata;
use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\ChangelogRepository;
use Marcth\GocDeploy\Repositories\GitRepository;

trait TagCommandTrait
{
    protected $metadata;
    protected $changelog;

    /**
     * Execute the console command.
     *
     * @param GitRepository $repository
     * @param ChangelogRepository $changelogRepository
     * @return void
     * @throws DirtyWorkingTreeException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
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

        dd($this->changelog);
        dd($this->metadata);
    }




}
