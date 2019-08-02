<?php

namespace RoyVoetman\FlysystemGitlab\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use RoyVoetman\FlysystemGitlab\Client;
use RoyVoetman\FlysystemGitlab\GitlabAdapter;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var array
     */
    protected $config;
    
    /**
     *
     */
    public function setUp()
    {
        $this->config = require(__DIR__.'/config/config.testing.php');
    }
    
    /**
     * @return \RoyVoetman\FlysystemGitlab\Client
     */
    protected function getClientInstance(): Client
    {
        return new Client($this->config[ 'personal-access-token' ], $this->config[ 'project-id' ],
            $this->config[ 'branch' ], $this->config[ 'base-url' ]);
    }
    
    /**
     * @return \RoyVoetman\FlysystemGitlab\GitlabAdapter
     */
    protected function getAdapterInstance(): GitlabAdapter
    {
        return new GitlabAdapter($this->getClientInstance());
    }
}