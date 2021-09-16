<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
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
use Marcth\GocDeploy\Traits\TagCommandTrait;

/**
 */
class TagCommand extends Command
{
    use TagCommandTrait {
        TagCommandTrait::handle as handleTag;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssc:tag
                            {working_tree?  : The path to the local working repository.}
                            {deploy_branch? : The name of the branch to deploy.}
                            {main_branch?   : The name of the main/master branch or branch to tag.}
                            {--C|clone      : Clone a working repository into a new temporary directory.}
                            ';

    /**
     * The console command description.
     *
     * @var string
     * @todo I am Tag!
     */
    protected $description = 'I am Tag!';

    /**
     * Execute the console command.
     *
     * @param GitRepository $repository
     * @param ChangelogRepository $changelogRepository
     * @return void
     *
     * @throws ConnectionRefusedException
     * @throws DirtyWorkingTreeException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function handle(GitRepository $repository, ChangelogRepository $changelogRepository)
    {
        $this->handleTag($repository, $changelogRepository);
    }
}
