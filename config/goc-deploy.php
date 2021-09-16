<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | GOC Deploy is a non-reusable, workflow-specific package
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => 'GOC Deploy',

    /*
    |--------------------------------------------------------------------------
    | Default values used when not provided as console inputs
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        /*
        |--------------------------------------------------------------------------
        | The default path to the local git directory that is associated with a
        | repository.
        |
        | DEPRECATED
        |--------------------------------------------------------------------------
        */
        'working_tree' => env('GOC_DEPLOY_DEFAULT_WORKING_TREE', base_path()),

        /*
        |--------------------------------------------------------------------------
        | The default URL of the remote git directory that is associated with this
        | project.
        |--------------------------------------------------------------------------
        */
        'repository_url' => env('GOC_DEPLOY_DEFAULT_REPOSITORY_URL', NULL),

        /*
        |--------------------------------------------------------------------------
        | The default git branch containing the development code that should be
        | deployed to a staging or production environments.
        |--------------------------------------------------------------------------
        */
        'deploy_branch' => env('GOC_DEPLOY_DEFAULT_DEPLOY_BRANCH', 'develop'),

        /*
        |--------------------------------------------------------------------------
        | The default main (master) git branch the development code should be
        | merged into during the deployment process.
        |--------------------------------------------------------------------------
        */
        'main_branch' => env('GOC_DEPLOY_DEFAULT_MAIN_BRANCH', 'master'),

        /*
        |--------------------------------------------------------------------------
        | The default path to the CHANGELOG file where the message associated with
        | the tagged release will be extracted.
        |--------------------------------------------------------------------------
        */
        'changelog_path' => base_path() . '/CHANGELOG',
    ],


];
