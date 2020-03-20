<?php

namespace RoyVoetman\FlysystemGitlab\Tests;

use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Config;
use LogicException;
use RoyVoetman\FlysystemGitlab\Client;
use RoyVoetman\FlysystemGitlab\GitlabAdapter;

class GitlabAdapterDebugModeTest extends TestCase
{
    /**
     * @var \RoyVoetman\FlysystemGitlab\GitlabAdapter
     */
    protected $gitlabAdapter;
    
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->gitlabAdapter = $this->getAdapterInstance();
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_debug_mode()
    {
        $this->assertFalse($this->gitlabAdapter->isDebugEnabled());
    }
    
    /**
     * @test
     */
    public function it_can_update_debug_mode()
    {
        $this->gitlabAdapter->setDebug(true);
        
        $this->assertTrue($this->gitlabAdapter->isDebugEnabled());
        
        $this->gitlabAdapter->setDebug(false);
        
        $this->assertFalse($this->gitlabAdapter->isDebugEnabled());
    }
    
    /**
     * @test
     */
    public function it_can_initialize_with_debug_mode()
    {
        $adapter = new GitlabAdapter($this->getClientInstance(), '', true);
        
        $this->assertTrue($adapter->isDebugEnabled());
        
        $adapter = new GitlabAdapter($this->getClientInstance(), '', false);
        
        $this->assertFalse($adapter->isDebugEnabled());
    }
    
    /**
     * @test
     */
    public function it_throws_exception_in_debug_mode()
    {
        $client = new Client('my-invalid-token', $this->config[ 'project-id' ], $this->config[ 'branch' ],
            $this->config[ 'base-url' ]);
    
        $adapter = new GitlabAdapter($client, '', true);
    
        $this->expectException(GuzzleException::class);
    
        $adapter->read('README.md');
    }
    
    /**
     * @test
     */
    public function it_does_not_throws_exception_in_production_mode()
    {
        $client = new Client('my-invalid-token', $this->config[ 'project-id' ], $this->config[ 'branch' ],
            $this->config[ 'base-url' ]);
        
        $adapter = new GitlabAdapter($client, '');
        
        $this->assertFalse($adapter->read('README.md'));
    }
    
    /**
     * @test
     */
    public function it_throws_exception_in_debug_mode_when_listing_contents()
    {
        $client = new Client('my-invalid-token', $this->config[ 'project-id' ], $this->config[ 'branch' ],
            $this->config[ 'base-url' ]);
        
        $adapter = new GitlabAdapter($client, '', true);
        
        $this->expectException(GuzzleException::class);
        
        $adapter->listContents();
        
        $adapter->setDebug(false);
        
        $this->assertTrue($adapter->listContents() === []);
    }
}