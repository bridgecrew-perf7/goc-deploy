<?php

return [
    /*
    |--------------------------------------------------------------------------
    | The  URL of the remote git directory of this project.
    |--------------------------------------------------------------------------
    */
//    'repository_url' => '',

    /*
    |--------------------------------------------------------------------------
    | The deployment directory where this application's working tree exists in
    | a subdirectory. If the working tree subdirectory does not exist, it will
    | be cloned from the remote git repository.
    |
    |Note: The local working tree of the application cannot be the same as the
    |      local working tree where this command is executed from.
    |--------------------------------------------------------------------------
    */
    'base_deploy_path' => '/var/www/deploy',

    /*
    |--------------------------------------------------------------------------
    | Recommended. The relative path to the CHANGELOG file describing releases
    | from the repository root. If defined, the first paragraph delimited by
    | a empty line is used as the git tag message for the release.
    |--------------------------------------------------------------------------
    */
    'changelog' => './src/CHANGELOG',

    /*
    |--------------------------------------------------------------------------
    | Optional. An array of localized message catalogs to be compiled via
    | msgfmt (e.g. gettext, symfony).
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
        | The default git branch containing the development code that should be
        | deployed to a staging or production environments.
        |--------------------------------------------------------------------------
        */
        'merge_branch' => 'develop',

        /*
        |--------------------------------------------------------------------------
        | The default main (master) git branch the development code should be
        | merged into during the deployment process.
        |--------------------------------------------------------------------------
        */
        'main_branch' => 'master',
    ],
];
