<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Flysystem Adapter for Gitlab configurations
    |--------------------------------------------------------------------------
    |
    | These configurations will be used in all the the tests to bootstrap
    | a Client object.
    |
    */
    
    /**
     * Personal access token
     *
     * @see https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html#creating-a-personal-access-token
     */
    'personal-access-token' => 'your-access-token',
    
    /**
     * Project id of your repo
     */
    'project-id'            => 'your-project-id',
    
    /**
     * Branch that should be used
     */
    'branch'                => 'master',
    
    /**
     * Base URL of Gitlab server you want to use
     */
    'base-url'              => 'https://gitlab.com',
];
