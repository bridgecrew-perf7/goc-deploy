<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Marcth\GocDeploy\Traits\TagCommandTrait;

/**
 */
class TagCommand extends Command
{
    use TagCommandTrait;

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
}
