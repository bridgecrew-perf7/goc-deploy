<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Marcth\GocDeploy\Repositories\GitRepository;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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
     */
    public function handle()
    {
        dd(__METHOD__);
        $workingTree = $this->argument('working_tree') ?? config('goc-deploy.defaults.working_tree');
        $deployBranch = $this->argument('deploy_branch') ?? config('goc-deploy.defaults.deploy_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $gitRepository = new GitRepository($workingTree);

//getRemoteUrl
//refreshOriginMetadata
//execute
//process
        $return = [
            $workingTree => $workingTree,
            $deployBranch => $deployBranch,
            $mainBranch => $mainBranch,
        ];

        dd($return);
    }


}
