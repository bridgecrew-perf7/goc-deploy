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
class TpdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goc-deploy:tpd
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
    protected $description = 'goc-deploy:tag -> goc-deploy:package -> goc-deploy:deploy';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {


        $arguments = [
            'working_tree' => $this->argument('working_tree'),
            'deploy_branch' => $this->argument('deploy_branch'),
            'main_branch' => $this->argument('main_branch'),
        ];

        if($this->option('clone')) {
            $arguments['--clone'] = 'default';
        }


        $data = $this->call('goc-deploy:tpd', $arguments);

        dd($data);
    }

}
