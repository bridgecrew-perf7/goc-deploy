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
 * Requires git
 * Requires Linux (mktemp -d)
 */
class DeployCommand extends Command
{
//    use TagCommandTrait {
//        TagCommandTrait::handle as handleTag;
//    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssc:deploy
                            {working_tree?  : The path to the local working repository.}
                            {deploy_branch? : The name of the branch to deploy.}
                            {main_branch?   : The name of the main/master branch or branch to tag.}
                            {--C|clone      : Clone a working repository into a new temporary directory.}
                            ';

    /**
     * The console command description.
     *
     * @var string
     * @todo I am Deploy!
     */
    protected $description = 'I am Deploy!';

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
        $workingTree = $this->argument('working_tree') ?? config('goc-deploy.defaults.working_tree');
        $deployBranch = $this->argument('deploy_branch') ?? config('goc-deploy.defaults.deploy_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $gitRepository = new GitRepository();

        $workingTree .= DIRECTORY_SEPARATOR . 'src';

        $gitRepository->package($mainBranch, $workingTree);
        exit(__METHOD__);
/*
 * #!/bin/bash
#https://gccode.ssc-spc.gc.ca/ssc-innovation-group/gcsx/-/wikis/deployment-steps
export TODAY=`date +"%Y-%m-%d"`

cd ../../src

php artisan view:clear &&
php artisan route:clear &&
php artisan event:clear &&
#php artisan debugbar:clear &&
php artisan config:clear &&
php artisan cache:clear &&
php artisan clear-compiled &&
php artisan passport:purge

msgfmt ./resources/i18n/en_CA/LC_MESSAGES/messages.po -o ./resources/i18n/en_CA/LC_MESSAGES/messages.mo
msgfmt ./resources/i18n/fr_CA/LC_MESSAGES/messages.po -o ./resources/i18n/fr_CA/LC_MESSAGES/messages.mo

composer install --optimize-autoloader --no-dev
bower install
npm install
npm run production

read -p "Build Completed, Press any key to continue..."

cd ..

tar --exclude-vcs \
    --exclude="./src/bower_components" \
    --exclude="./src/node_modules" \
    --exclude="./src/storage" \
    -zvcf \
    "passport-src_${TODAY}.tar.gz" \
    ./src


//tar --exclude-vcs --exclude='.idea' --exclude='.git' --exclude='.gitlab' --exclude='storage' --exclude='tests' --exclude='.env' --exclude='.gitignore' --exclude='.editorconfig' --exclude='scripts' --exclude='bootstrap/cache/*' -zvcf "gcsx-src_${TODAY}.tar.gz" ./gcsx

mv "passport-src_${TODAY}.tar.gz" ../

cd src
composer install
npm run dev
cd ../scripts/deploy

 */
    }
}
