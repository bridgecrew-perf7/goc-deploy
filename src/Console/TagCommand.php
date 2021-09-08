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
        $gitRepository = new GitRepository(
            $this->argument('working_tree') ??
            config('goc-deploy.defaults.working_tree'));

        $return = [
            'name' => $name ?? null,
            'workingTree' => $gitRepository->getWorkingTree(),
            'remoteUrl' => $gitRepository->getRemoteUrl(),
            'deploy' => [
                'branch' => $this->argument('deploy_branch') ?? config('goc-deploy.defaults.deploy_branch'),
                'tag' => null,
            ],
            'main' => [
                'branch' => $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch'),
                'tag' => null,
            ],
            'initial' => [
                'branch' => $gitRepository->getCurrentBranch(),
                'workingTree' => $gitRepository->getWorkingTree(),
            ],
        ];

        $return['name'] = $gitRepository->parseNameFromUrl();

        $gitRepository->refreshOriginMetadata();

        if(!$this->option('clone')) {
            if($gitRepository->hasLocalChanges()) {
                $this->newLine();
                $this->error(" ERROR: Resolve the uncommitted/untracked changes in your working branch.");
                $this->newLine();
                $this->line(" Hint: The '--clone' option can be used to bypass this issue.");
                $this->newLine();

                exit(1);
            }
        } else {
            $this->newLine();
            $this->line("Cloning `" . $return['remoteUrl'] . "` to a temporary directory.");
            $this->line('This may take a few moments...');

            $return['workingTree'] = $gitRepository->cloneToTemp();

            $this->info('The repository has been cloned to `' . $return['workingTree'] . '`.');
            $this->newLine();
        }


        $return['deploy']['tag'] = $gitRepository->getCurrentTag($return['deploy']['branch']);
        $return['deploy']['version'] = $gitRepository->parseVersionDetailsFromTag($return['deploy']['tag']);

        $return['main']['tag'] = $gitRepository->getCurrentTag($return['main']['branch']);
        $return['main']['version'] = $gitRepository->parseVersionDetailsFromTag($return['main']['tag']);

        dd($return);
    }


}
