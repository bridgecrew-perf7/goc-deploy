<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    | GOC Deploy is a non-reusable, workflow-specific package.
    */
    'name' => 'GOC Deploy',

    /*
    |--------------------------------------------------------------------------
    | The default URL of the remote git directory that is associated with this
    | project.
    |--------------------------------------------------------------------------
    */
    //'repository_url' => env('GOC_DEPLOY_DEFAULT_REPOSITORY_URL', NULL),

    /*
    |--------------------------------------------------------------------------
    | The default path to the CHANGELOG file where the message associated with
    | the tagged release will be extracted.
    |--------------------------------------------------------------------------
    */
    'changelog' => base_path() . '/CHANGELOG',

    /*
    |--------------------------------------------------------------------------
    | Optional. An array of localized message catalogs that can be compiled to
    | binary format via msgfmt (e.g. gettext).
    |--------------------------------------------------------------------------
    */
    'lc_message_catalogs' => [
        //'en_CA' => base_path('resources/i18n/en_CA/LC_MESSAGES/messages.po'),
        //'fr_CA' => base_path('resources/i18n/fr_CA/LC_MESSAGES/messages.po'),
    ],

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
    ],


];
