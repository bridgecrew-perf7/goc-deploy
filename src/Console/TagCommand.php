<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\GitRepository;

/**
 * Requires git
 * Requires Linux (mktemp -d)
 */
class TagCommand extends Command
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
     * Execute the console command.
     *
     * @return void
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function handle()
    {
        $deployBranch = $this->argument('deploy_branch') ?? config('goc-deploy.defaults.deploy_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $gitRepository = new GitRepository(
            $this->argument('working_tree') ??
            config('goc-deploy.defaults.working_tree'));

        $gitRepository->refreshOriginMetadata();

        $return = [
            'name' => $name ?? null,
            'workingTree' => $gitRepository->getWorkingTree(),
            'remoteUrl' => $gitRepository->getRemoteUrl(),
            'initial' => [
                'branch' => $gitRepository->getCurrentBranch(),
                'workingTree' => $gitRepository->getWorkingTree(),
            ],
            'deployBranch' => $gitRepository->hasBranch($deployBranch),//->getCurrentBranch(),
            'mainBranch' => $mainBranch,
        ];


        //->1checkoutBranch

        $return['name'] = basename($gitRepository->getWorkingTree());


        dd($return);
    }


}
