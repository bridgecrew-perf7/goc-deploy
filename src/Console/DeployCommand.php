<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Marcth\GocDeploy\Entities\GitMetadata;
use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\GitMergeConflictException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\Repository;

class DeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssc:deploy
                            {merge_branch?    : The name of the branch to merge into main_branch.}
                            {main_branch?     : The name of the main/master branch to tag, package and release.}
                            {deployment_path? : The path to the base deployment directory (i.e. Not this working tree).}
                            ';

    /**
     * The console command description.
     *
     * @var string
     * @todo I am Deploy!
     */
    protected $description = 'I am Deploy!';

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Execute the console command.
     *
     * @param Repository $repository
     * @return void
     *
     * @throws DirtyWorkingTreeException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function handle(Repository $repository)
    {
        $this->repository = $repository;
        /*
        $mergeBranch = $this->argument('merge_branch') ?? config('goc-deploy.defaults.merge_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $workingTree = $this->initializeDeploymentWorkingTree(config('goc-deploy.base_deploy_path'));
        $metadata = GitMetadata::make($workingTree, $mergeBranch, $mainBranch);
        $changelog = $this->parseChangelog($metadata->workingTree . '/' . config('goc-deploy.changelog'));
        $composerPath = $metadata->workingTree . DIRECTORY_SEPARATOR . config('goc-deploy.composer_json_path');

        $this->outputRepositorySummary($metadata);
        $this->outputBranchVersionSummaries($metadata);
        $this->outputChangelog($changelog);


        $question = 'Please enter the tag reference for this release to staging:';
        $releaseTag = $this->ask($question, $this->getReleaseTagSuggestion($metadata));

             $question = 'Do you want to merge "%s" into "%s" and reference it with tag "%s" using the changelog above?';
             $question = sprintf($question, $metadata->mergeBranch->name, $metadata->mainBranch->name, $releaseTag);

             if (!$this->confirm($question)) {
                 $this->warn('Aborted by user.');
                 $this->newLine();
                 exit(0);
             }

             $this->mergeBranch($metadata, $releaseTag, $changelog);


            $this->confirmVpnDisconnected();
            $this->composerUpdate($composerPath, false);
            $this->compileMessageCatalog(config('goc-deploy.lc_message_catalogs'));
            $this->npmInstall($composerPath);
            $this->npmRun($composerPath, true);


            $tarball = $this->package($metadata, $composerPath, config('goc-deploy.base_deploy_path'), $releaseTag);
        */

        $tarball = '/var/www/deploy/passport_1.25.0-rc.2_202109212323.tar.gz';

       exec('docker build --network host -t mtweb/ssc-deploy:v1.0.0 '  . realpath(__DIR__ . '/../../runtimes'), $output, $code);

        $command = [
            'docker run',
            '--rm',
            '--privileged',
            '--net="host"',
            '--cap-add NET_ADMIN',
            '--device /dev/net/tun',
            '--volume ' . $tarball . ':/transfer/' . basename($tarball),
            '--env TARBALL=' . basename($tarball),
            '--env SSH_KNOWN_HOSTS=/root/.ssh/known_hosts',
            '--env-file ' . realpath(__DIR__ . '/../../runtimes/opting.conf'),
            'mtweb/ssc-deploy:v1.0.0',
        ];



        dd(implode(" ", $command));
/*
        IMAGE_NAME="mtweb/wormhole-transfer-agent:v1.0.1"

    VOLUME_MOUNT_LOG=$(pwd)/log
    VOLUME_MOUNT_TRANSFER_QUEUE=$(pwd)/transfer_queue

    SSH_KNOWN_HOSTS="/root/.ssh/known_hosts"

  COMMAND=$1
  CONFIG_FILE=$2
  CONTAINER_NAME="$(basename -- $CONFIG_FILE .conf)-wormhole"
  TRANSFER_QUEUE="/transfer_queue"

  if [ $(docker ps -q --filter name=$CONTAINER_NAME) ]; then
    echo "There is already a running container with the name '$CONTAINER_NAME'."
    echo
    exit 1
  fi

  # Build the docker image
  docker build -t $IMAGE_NAME .

    # Ensure local volume mounts exist
    mkdir -p \
    $VOLUME_MOUNT_LOG/$CONTAINER_NAME \
    $VOLUME_MOUNT_TRANSFER_QUEUE/$CONTAINER_NAME

  # Run container
  docker run --rm -d \
      --privileged \
      --cap-add NET_ADMIN \
      --device /dev/net/tun \
      --volume $VOLUME_MOUNT_LOG/$CONTAINER_NAME:/log \
      --volume $VOLUME_MOUNT_TRANSFER_QUEUE/$CONTAINER_NAME:$TRANSFER_QUEUE \
      --env TRANSFER_QUEUE=$TRANSFER_QUEUE \
      --env SSH_KNOWN_HOSTS=$SSH_KNOWN_HOSTS \
      --env-file $CONFIG_FILE \
      --name $CONTAINER_NAME \
      $IMAGE_NAME

*/

        /*


//dd(config('goc-deploy.base_deploy_path'));
        dd($output);
//        $this->info($output);
        */
        exit(0);
    }


    /**
     * @param string $branch
     * @param string $workingTree
     * @return $this
     * @throws ProcessException
     */
    protected function checkoutBranch(string $branch, string $workingTree): self
    {
        $this->info('Checking out "' . $branch . '" branch.');
        $this->repository->checkoutBranch($branch, $workingTree);

        return $this;
    }

    /**
     * Generates binary message catalog from textual translation description.
     *
     * @param array|null $messageCatalogs
     */
    protected function compileMessageCatalog(?array $messageCatalogs)
    {
        if ($messageCatalogs) {
            foreach ($messageCatalogs as $messageCatalog) {
                $this->info('Compiling message catalog "' . $messageCatalog . '".');
                $this->repository->compileMessageCatalog($messageCatalog);
            }
        }
    }

    /**
     * Downloads and installs all the libraries and dependencies outlined in the composer.lock file.
     *
     * @param string $composerPath
     * @param bool $includeDevPackages
     * @return $this
     */
    protected function composerUpdate(string $composerPath, bool $includeDevPackages): self
    {
        $disconnectedFromVpn = false;

        while(!$disconnectedFromVpn) {
            $this->info('Installing non-development composer dependencies from composer.json.');
            $disconnectedFromVpn = $this->repository->composerUpdate($composerPath, $includeDevPackages);

            if (!$disconnectedFromVpn) {
                $this->confirmVpnDisconnected();
            }
        }

        return $this;
    }

// Unused
//    /**
//     * Prompts the user to ensure Windows is connected to "vpn.ssc.gc.ca".
//     *
//     * @return bool
//     */
//    protected function confirmVpnConnected(): bool
//    {
//        while(!$this->confirm(
//            'Please ensure Windows is connected to "vpn.ssc.gc.ca" before continuing.',
//            true
//        ));
//
//        return true;
//    }

    /**
     * Prompts the user to ensure Windows is disconnected from "vpn.ssc.gc.ca".
     *
     * @return bool
     */
    protected function confirmVpnDisconnected(): bool
    {
        while(!$this->confirm(
            'Please ensure Windows is disconnected from "vpn.ssc.gc.ca" before continuing.',
            true
        ));

        return true;
    }

    /**
     * Returns a release tag suggestion to a staging environment.
     *
     * @param GitMetadata $metadata
     * @return string
     */
    protected function getReleaseTagSuggestion(GitMetadata $metadata): string
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

        return $suggestion;
    }

    /**
     * @param string $baseDeployPath
     * @return string
     * @todo  Document
     */
    public function initializeDeploymentWorkingTree(string $baseDeployPath): string
    {
        $this->newLine();
        $this->line('Initializing local deployment working tree.');

        $repositoryUrl = $this->repository->getRemoteUrl(base_path());
        $repositoryName = $this->repository->parseNameFromUrl($repositoryUrl);

        $baseDeployPath = $this->repository->makeDirectories($baseDeployPath);
        $deploymentPath = $baseDeployPath . DIRECTORY_SEPARATOR . $repositoryName;

        try {
            $deploymentPathRepositoryUrl = $this->repository->getRemoteUrl($deploymentPath);

            if($deploymentPathRepositoryUrl != $repositoryUrl) {
                $this->newLine();
                $this->warn('The deployment working tree "' . $deploymentPath . '" contains the wrong repository.');

                if($this->confirm('Would you like to delete "' . $deploymentPath . '"?', true)) {
                    $this->repository->delete($deploymentPath);
                    throw new ProcessException('Invalid Repository');
                } else {
                    $this->newLine();
                    $this->info('Aborted by user.');
                    $this->newLine();
                }
            }
        } catch(ProcessException $e) {
            $this->info('Cloning "' . $repositoryUrl . '" to "' . $deploymentPath . '".');
            $this->repository->clone($repositoryUrl, $baseDeployPath);
        }

        $this->info('Fetching references and metadata from origin.');
        $this->repository->refreshOriginMetadata($deploymentPath);

        $this->info('Validating working tree.');
        $this->repository->validateWorkingTree($deploymentPath);

        $this->newLine();

        return realpath($deploymentPath);
    }

    /**
     *
     *
     * @param GitMetadata $metadata
     * @param string $releaseTag
     * @param string|null $changelog
     * @return bool
     * @throws GitMergeConflictException
     * @throws ProcessException
     */
    protected function mergeBranch(GitMetadata $metadata, string $releaseTag, ?string $changelog): bool
    {
        $this->checkoutBranch($metadata->mergeBranch->name, $metadata->workingTree);
        $this->pullFromRemote($metadata->workingTree);

        $this->checkoutBranch($metadata->mainBranch->name, $metadata->workingTree);
        $this->pullFromRemote($metadata->workingTree);

        try {
            $this->info(sprintf(
                'Attempting to merge "%s" into "%s" with tag reference "%s".',
                $metadata->mergeBranch->name,
                $metadata->mainBranch->name,
                $releaseTag
            ));

            $this->repository->mergeBranch($metadata->mergeBranch->name, $metadata->workingTree);
            $this->repository->tagBranch($releaseTag, $metadata->workingTree, $changelog ?? 'N/A');

            $this->info("Pushing changes to remote repository with tag reference.");
            $this->repository->pushToRemote($metadata->workingTree, true);

            $this->info(sprintf(
                'Attempting to merge "%s" into "%s".',
                $metadata->mainBranch->name,
                $metadata->mergeBranch->name,
            ));

            $this->repository->checkoutBranch($metadata->mergeBranch->name, $metadata->workingTree);
            $this->repository->mergeBranch($metadata->mainBranch->name, $metadata->workingTree);

            $this->info("Pushing changes to remote repository.");
            $this->repository->pushToRemote($metadata->workingTree, false);
        } catch (GitMergeConflictException $e) {
            $this->warn('A conflict occurred while performing a git merge. Attempting to abort...');
            $this->repository->abortMergeBranch($metadata->workingTree);

            throw $e;
        }

        return true;
    }


    /**
     * @param string $packageJsonPath
     * @return bool
     * @todo  No error handling
     */
    protected function npmInstall(string $packageJsonPath): bool
    {
        $this->info('Installing all npm modules and dependencies that are listed in package.json.');
        return $this->repository->npmInstall($packageJsonPath);
    }

    /**
     * Executes `npm run develop` or `npm run production` in the specified directory.
     * @param string $packageJsonPath
     * @param bool $productionEnv
     * @return bool
     * @todo  No error handling
     */
    protected function npmRun(string $packageJsonPath, bool $productionEnv=false): bool
    {
        if($productionEnv) {
            $this->info('Compiling and minifying all production public assets.');
        } else {
            $this->info('Compiling all public assets including a source map.');
        }

        return $this->repository->npmRun($packageJsonPath, $productionEnv);
    }

    /**
     * Outputs the contents of the specified $changelog array to the console.
     *
     * @param string $changelog
     * @return $this
     */
    protected function outputChangelog(string $changelog): self
    {
        $tableData = [];
        $changelog = explode("\n", $changelog);

        foreach ($changelog as $line) {
            $tableData[][] = $line;
        }

        $this->info('Changelog for Release');
        $this->table([], $tableData);
        $this->newLine();

        return $this;
    }

    /**
     * Outputs the deployment and main branches' version summaries to the console.
     *
     * @param GitMetadata $metadata
     * @return $this
     */
    protected function outputBranchVersionSummaries(GitMetadata $metadata): self
    {
        $this->newLine();
        $this->info('Branch Version Summaries');
        $this->table(['Branch', 'Major', 'Minor', 'Patch', 'Type', 'Descriptor', 'Revision'],
            [
                [
                    $metadata->mergeBranch->name . ' (Merge)',
                    $metadata->mergeBranch->version->major,
                    $metadata->mergeBranch->version->minor,
                    $metadata->mergeBranch->version->patch,
                    $metadata->mergeBranch->version->type,
                    $metadata->mergeBranch->version->descriptor,
                    $metadata->mergeBranch->version->revision,
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
     * Outputs the git repository summary details to the console.
     *
     * @param GitMetadata $metadata
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
                ($metadata->mergeBranch->name ?? null) . ' (' . ($metadata->mergeBranch->tag ?? null) . ')'
            ],
            [
                'Main Branch (Tag)',
                ($metadata->mainBranch->name ?? null) . ' (' . ($metadata->mainBranch->tag ?? null) . ')'
            ],
        ]);

        return $this;
    }

    /**
     * @param GitMetadata $metadata
     * @param string $workingTree
     * @param string $destination
     * @param string $releaseTag
     * @return string
     * @throws ProcessException
     */
    public function package(GitMetadata $metadata, string $workingTree, string $destination, string $releaseTag): string
    {
        $tarball = $destination . DIRECTORY_SEPARATOR . sprintf(
            '%s_%s_%s.tar.gz',
            $metadata->name,
            $releaseTag,
            Carbon::now()->format('YmdHs')
        );

        $this->info('Compressing "' . $workingTree . '" into "' . $tarball . '" (This may take a few moments).');
        $this->repository->package($workingTree, $tarball);
        $this->line('"' . $tarball . '" has been saved.');

        return $tarball;
    }

    /**
     * Extracts the first paragraph contained in the specified changelog file.
     *
     * @param string $changelog
     * @return string
     * @throws InvalidPathException
     */
    public function parseChangelog(string $changelog): string
    {
        $lines = $this->repository->readFile($changelog, 4096);
        $currentChangeLogEntries = [];

        foreach ($lines as $line) {
            if (!trim($line)) {
                break;
            }

            $currentChangeLogEntries[] = $line;
        }

        return implode("\n", $currentChangeLogEntries);
    }

    /**
     * Pulls any changes from the remote git repository.
     *
     * @param string $workingTree
     * @return $this
     * @throws ProcessException
     */
    protected function pullFromRemote(string $workingTree): self
    {
        $this->info('Pulling changes from remote git repository.');
        $this->repository->pullFromRemote($workingTree);

        return $this;
    }

}
