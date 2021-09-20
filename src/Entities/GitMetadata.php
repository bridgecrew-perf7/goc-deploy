<?php

namespace Marcth\GocDeploy\Entities;

use Marcth\GocDeploy\Exceptions\DirtyWorkingTreeException;
use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Marcth\GocDeploy\Repositories\Repository;

class GitMetadata extends Entity
{

    protected $attributes = [
        'name' => null,
        'url' => null,
        'workingTree' => null,
        'deployBranch' => null,
        'mainBranch' => null,
    ];


    /**
     * @param string $workingTree
     * @param string $deployBranch
     * @param string $mainBranch
     * @return GitMetadata
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws DirtyWorkingTreeException
     */
    public static function make(string $workingTree, string $deployBranch, string $mainBranch): self
    {
        $repository = new Repository();

        $instance = new GitMetadata([
            'url' => $repository->getRemoteUrl($workingTree),
            'workingTree' => $repository->validateWorkingTree($workingTree)->getLocalRootPath($workingTree),
            'deployBranch' => new GitBranch([
                'name' => $deployBranch,
                'tag' => $repository->getCurrentTag($deployBranch, $workingTree),
            ]),
            'mainBranch' => new GitBranch([
                'name' => $mainBranch,
                'tag' => $repository->getCurrentTag($mainBranch, $workingTree),
            ]),
        ]);

        $instance->name = basename($instance->url, '.git');
        $instance->deployBranch->version = new Version($repository->parseVersionDetailsFromTag($instance->deployBranch->tag));
        $instance->mainBranch->version = new Version($repository->parseVersionDetailsFromTag($instance->mainBranch->tag));

        return $instance;
    }
}
