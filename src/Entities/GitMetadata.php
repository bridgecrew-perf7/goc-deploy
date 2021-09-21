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
        'mergeBranch' => null,
        'mainBranch' => null,
    ];


    /**
     * @param string $workingTree
     * @param string $mergeBranch
     * @param string $mainBranch
     * @return GitMetadata
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @throws DirtyWorkingTreeException
     */
    public static function make(string $workingTree, string $mergeBranch, string $mainBranch): self
    {
        $repository = new Repository();

        $instance = new GitMetadata([
            'url' => $repository->getRemoteUrl($workingTree),
            'workingTree' => $repository->validateWorkingTree($workingTree)->getLocalRootPath($workingTree),
            'mergeBranch' => new GitBranch([
                'name' => $mergeBranch,
                'tag' => $repository->getCurrentTag($mergeBranch, $workingTree),
            ]),
            'mainBranch' => new GitBranch([
                'name' => $mainBranch,
                'tag' => $repository->getCurrentTag($mainBranch, $workingTree),
            ]),
        ]);

        $instance->name = basename($instance->url, '.git');
        $instance->mergeBranch->version = new Version($repository->parseVersionDetailsFromTag($instance->mergeBranch->tag));
        $instance->mainBranch->version = new Version($repository->parseVersionDetailsFromTag($instance->mainBranch->tag));

        return $instance;
    }
}
