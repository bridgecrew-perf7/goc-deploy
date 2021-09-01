<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => 'GOC Deploy',

    /*
    |--------------------------------------------------------------------------
    | The default path to the local git directory that is associated with a
    | repository.
    |--------------------------------------------------------------------------
    */
    'default_working_tree' => base_path(),

    /*
    |--------------------------------------------------------------------------
    | The default git branch containing the development code that should be
    | deployed to a staging or production environments.
    |--------------------------------------------------------------------------
    */
    'default_deploy_branch' => 'develop',

    /*
    |--------------------------------------------------------------------------
    | The default main (master) git branch the development code should be
    | merged into during the deployment process.
    |--------------------------------------------------------------------------
    */
    'default_main_branch' => 'master',

    /*
    |--------------------------------------------------------------------------
    | TBD
    |--------------------------------------------------------------------------
    */
    'changelog_path' => base_path() . '/master',
];
