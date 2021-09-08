<?php

namespace Marcth\GocDeploy\Traits;

use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\GitRepository;

trait TagCommandTrait
{

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
