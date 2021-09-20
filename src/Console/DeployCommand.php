<?php

namespace Marcth\GocDeploy\Console;

use Illuminate\Console\Command;
use Marcth\GocDeploy\Entities\GitMetadata;
use Marcth\GocDeploy\Exceptions\CompileTranslationException;
use Marcth\GocDeploy\Exceptions\ConnectionRefusedException;
use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\GitMergeConflictException;
use Marcth\GocDeploy\Exceptions\InvalidGitBranchException;
use Marcth\GocDeploy\Exceptions\InvalidGitReferenceException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\Repository;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Requires git
 */
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
     * @throws GitMergeConflictException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws ConnectionRefusedException
     */
    public function handle(Repository $repository)
    {
        $mergeBranch = $this->argument('merge_branch') ?? config('goc-deploy.defaults.merge_branch');
        $mainBranch = $this->argument('main_branch') ?? config('goc-deploy.defaults.main_branch');

        $baseDeployPath = config('goc-deploy.base_deploy_path');
        $changelog = config('goc-deploy.changelog');
        $messageCatalogs = config('goc-deploy.lc_message_catalogs');

        $this->repository = $repository;

        $deployTree = $this->initializeDeploymentWorkingTree($baseDeployPath);
        $metadata = GitMetadata::make($deployTree, $mergeBranch, $mainBranch);

        $this->outputRepositorySummary($metadata);
        $this->outputBranchVersionSummaries($metadata);
        $this->outputChangelog($this->parseChangelog($repository, $deployTree . '/' . $changelog));

        $question = 'Please enter the tag reference for this release to staging:';
        $releaseTag = $this->ask($question, $this->getReleaseTagSuggestion($metadata));

        $question = 'Do you want to merge "%s" into "%s" and reference it with tag "%s" using the changelog above?';
        $question = sprintf($question, $metadata->deployBranch->name, $metadata->mainBranch->name, $releaseTag);

        if (!$this->confirm($question)) {
            $this->warn('Aborted by user.');
            $this->newLine();
            exit(0);
        }

        $this->compileMessageCatalogs($repository, $messageCatalogs);

        $this->line('Installing non-development composer dependencies...');
        $repository->composerInstall($workingTree, false);
        $this->info('done.');

        dd(__METHOD__);

        $repository->package($workingTree);

        dd($metadata);
    }

    /**
     * @param string $baseDeployPath
     * @return string The $deployTree
     */
    public function initializeDeploymentWorkingTree(string $baseDeployPath): string
    {
        $this->newLine();
        $this->line('Initializing local deployment working tree.');

        $repositoryUrl = $this->repository->getRemoteUrl(base_path());
        $repositoryName = basename($repositoryUrl, '.git');

        $baseDeployPath = $this->repository->makeDirectories($baseDeployPath);
        $deployTree = $baseDeployPath . DIRECTORY_SEPARATOR . $repositoryName;

        try {
            $deployTreeRepositoryUrl = $this->repository->getRemoteUrl($deployTree);

            if($deployTreeRepositoryUrl != $repositoryUrl) {
                $this->newLine();
                $this->warn('The deployment working tree "' . $deployTree . '" contains the wrong repository.');

                if($this->confirm('Would you like to delete "' . $deployTree . '"?', true)) {
                    $this->repository->delete($deployTree);
                    throw new ProcessException('Invalid Repository');
                } else {
                    $this->newLine();
                    $this->info('Aborted by user.');
                    $this->newLine();
                }
            }
        } catch(ProcessException $e) {
            $this->line('Cloning "' . $repositoryUrl . '" to "' . $deployTree . '".');
            $this->repository->clone($repositoryUrl, $baseDeployPath);
        }

        $this->info('Fetching references and metadata from origin.');
        $this->repository->refreshOriginMetadata($deployTree);

        $this->info('Validating working tree.');
        $this->repository->validateWorkingTree($deployTree);

        $this->newLine();

        return $deployTree;
    }









    /**
     * Extrapolates the remote git URL from the specified working tree and clones the repository to a temporary
     * folder.
     *
     * @param Repository $repository
     * @param string $workingTree
     * @return string The temporary working tree of the remote repository.
     *
     * @throws ConnectionRefusedException
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    protected function cloneToTemp(Repository $repository, string $workingTree): string
    {
        $url = $repository->getRemoteUrl($workingTree);

        $this->newLine();
        $this->line('Cloning "' . $url . '" to a temporary directory.');
        $this->line('This may take a few moments...');

        $workingTree = $repository->cloneToTemp($url);

        $this->info('The repository has been cloned to "' . $workingTree . '".');
        $this->newLine();

        return $workingTree;
    }

    /**
     * @param Repository $repository
     * @param string $changelog Fully qualified path.
     * @return array
     */
    public function parseChangelog(Repository $repository, string $changelog): array
    {
        $lines = $repository->readFile($changelog, 4096);
        $currentChangeLogEntries = [];

        foreach ($lines as $line) {
            if (!trim($line)) {
                break;
            }

            $currentChangeLogEntries[] = $line;
        }

        return $currentChangeLogEntries;
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
                ($metadata->deployBranch->name ?? null) . ' (' . ($metadata->deployBranch->tag ?? null) . ')'
            ],
            [
                'Main Branch (Tag)',
                ($metadata->mainBranch->name ?? null) . ' (' . ($metadata->mainBranch->tag ?? null) . ')'
            ],
        ]);

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
                    $metadata->deployBranch->name . ' (Deploy)',
                    $metadata->deployBranch->version->major,
                    $metadata->deployBranch->version->minor,
                    $metadata->deployBranch->version->patch,
                    $metadata->deployBranch->version->type,
                    $metadata->deployBranch->version->descriptor,
                    $metadata->deployBranch->version->revision,
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
     * Outputs the contents of the specified $changelog array to the console.
     *
     * @param array $changelog
     * @return $this
     */
    protected function outputChangelog(array $changelog): self
    {
        $tableData = [];

        foreach ($changelog as $line) {
            $tableData[][] = $line;
        }

        $this->info('Changelog for Release');
        $this->table([], $tableData);
        $this->newLine();

        return $this;
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
     * @param Repository $repository
     * @param GitMetadata $metadata
     * @param string $releaseTag
     * @param array $changelogMessage
     * @return bool
     *
     * @throws GitMergeConflictException
     * @throws InvalidGitBranchException
     * @throws InvalidGitReferenceException
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws ConnectionRefusedException
     */
    protected function mergeBranch(
        Repository  $repository,
        GitMetadata $metadata,
        string      $releaseTag,
        array       $changelogMessage): bool
    {
        $repository->checkoutBranch($metadata->deployBranch->name, $metadata->workingTree);
        $repository->pullRemote($metadata->workingTree);

        $repository->checkoutBranch($metadata->mainBranch->name, $metadata->workingTree);
        $repository->pullRemote($metadata->workingTree);

        try {
            $repository->mergeBranch($metadata->deployBranch->name, $metadata->workingTree);
            $repository->tagBranch($releaseTag, $metadata->workingTree, implode("\n", $changelogMessage));

            $repository->pushToRemote($metadata->workingTree);
            $repository->pushTagsToRemote($metadata->workingTree);

            $repository->checkoutBranch($metadata->deployBranch->name, $metadata->workingTree);
            $repository->pullRemote($metadata->workingTree);

            $repository->mergeBranch($metadata->mainBranch->name, $metadata->workingTree);
            $repository->pushToRemote($metadata->workingTree);
        } catch (GitMergeConflictException $e) {
            $this->warn('Aborting git merge...');

            $repository->abortMergeBranch($metadata->workingTree);

            throw $e;
        }

        return true;
    }

    /**
     * @param Repository $repository
     * @param array|null $messageCatalog
     * @return $this
     *
     * @throws CompileTranslationException
     */
    public function compileMessageCatalogs(Repository $repository, ?array $messageCatalogs): self
    {
        if ($messageCatalogs) {
            foreach ($messageCatalogs as $messageCatalog) {
                $this->info('Compiling message catalog "' . $messageCatalog . '"....');
                $repository->compileMessageCatalog($messageCatalog);
            }
        }

        $this->newLine();

        return $this;
    }

}
