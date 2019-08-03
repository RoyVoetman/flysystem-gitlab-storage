<?php

namespace RoyVoetman\FlysystemGitlab\Tests;

use League\Flysystem\Config;
use LogicException;
use RoyVoetman\FlysystemGitlab\Client;
use RoyVoetman\FlysystemGitlab\GitlabAdapter;

class GitlabAdapterTest extends TestCase
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
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(GitlabAdapter::class, $this->getAdapterInstance());
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_client_instance()
    {
        $this->assertInstanceOf(Client::class, $this->gitlabAdapter->getClient());
    }
    
    /**
     * @test
     */
    public function it_can_set_client_instance()
    {
        $this->setInvalidProjectId();
        
        $this->assertEquals($this->gitlabAdapter->getClient()
            ->getProjectId(), '123');
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_write_a_file()
    {
        $metadata = $this->gitlabAdapter->write('testing.md', '# Testing create', new Config());
        
        $this->assertStringStartsWith(base64_encode('# Testing create'), $metadata[ 'content' ]);
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_write_failed()
    {
        $this->setInvalidProjectId();
        
        $res = $this->gitlabAdapter->write('testing.md', '# Testing create', new Config());
        
        $this->assertFalse($res);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_write_a_file_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        
        $metadata = $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        
        fclose($stream);
        
        $this->assertStringStartsWith(base64_encode('File for testing file streams'), $metadata[ 'content' ]);
        $this->assertEquals($metadata[ 'file_name' ], 'testing.txt');
        
        // Clean up
        $this->gitlabAdapter->delete('testing.txt');
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_writing_file_stream_failed()
    {
        $this->setInvalidProjectId();
        
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        
        $res = $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        
        fclose($stream);
        
        $this->assertFalse($res);
        
        // Clean up
        $this->restoreProjectId();
    
        $this->gitlabAdapter->delete('testing.txt');
    }
    
    /**
     * @test
     */
    public function it_can_update_a_file()
    {
        $metadata = $this->gitlabAdapter->update('testing.md', '# Testing update', new Config());
        
        $this->assertStringStartsWith(base64_encode('# Testing update'), $metadata[ 'content' ]);
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_update_failed()
    {
        $this->setInvalidProjectId();
        
        $res = $this->gitlabAdapter->update('testing.md', '# Testing update', new Config());
        
        $this->assertFalse($res);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_update_a_file_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        
        $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        
        fclose($stream);
        
        $stream = fopen(__DIR__.'/assets/testing-update.txt', 'r+');
        
        $metadata = $this->gitlabAdapter->updateStream('testing.txt', $stream, new Config());
        
        fclose($stream);
        
        $this->assertStringStartsWith(base64_encode('File for testing file streams!'), $metadata[ 'content' ]);
        $this->assertEquals($metadata[ 'file_name' ], 'testing.txt');
        
        // Clean up
        $this->gitlabAdapter->delete('testing.txt');
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_update_file_stream_failed()
    {
        $this->setInvalidProjectId();
        
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        
        $res = $this->gitlabAdapter->updateStream('testing.txt', $stream, new Config());
        
        fclose($stream);
        
        $this->assertFalse($res);
        
        // Clean up
        $this->restoreProjectId();
        
        $this->gitlabAdapter->delete('testing.txt');
    }
    
    /**
     * @test
     */
    public function it_can_rename_a_file()
    {
        $res = $this->gitlabAdapter->rename('testing.md', 'testing_renamed.md');
        
        $this->assertTrue($res);
    
        $res = $this->gitlabAdapter->has('testing_renamed.md');
    
        $this->assertTrue($res);
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_rename_failed()
    {
        $this->setInvalidProjectId();
        
        $res = $this->gitlabAdapter->rename('testing.md', 'testing_renamed.md');
        
        $this->assertFalse($res);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_copy_a_file()
    {
        $res = $this->gitlabAdapter->copy('testing_renamed.md', 'testing.md');
        
        $this->assertTrue($res);
    
        $res = $this->gitlabAdapter->has('testing.md');
    
        $this->assertTrue($res);
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_copy_failed()
    {
        $this->setInvalidProjectId();
        
        $res = $this->gitlabAdapter->copy('testing_renamed.md', 'testing.md');
        
        $this->assertFalse($res);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_delete_a_file()
    {
        $res1 = $this->gitlabAdapter->delete('testing_renamed.md');
        $res2 = $this->gitlabAdapter->delete('/testing.md');
        
        $this->assertTrue($res1);
        $this->assertTrue($res2);
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_delete_failed()
    {
        $this->setInvalidProjectId();
        
        $res = $this->gitlabAdapter->delete('testing_renamed.md');
        
        $this->assertFalse($res);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_determine_if_a_project_has_a_file()
    {
        $res = $this->gitlabAdapter->has('/README.md');
        
        $this->assertTrue($res);
    
        $res = $this->gitlabAdapter->has('/I_DONT_EXIST.md');
    
        $this->assertFalse($res);
    }
    
    /**
     * @test
     */
    public function it_can_create_a_directory()
    {
        $res = $this->gitlabAdapter->createDir('/testing', new Config());

        $this->assertTrue($res);

        $res = $this->gitlabAdapter->has('/testing/.gitkeep');

        $this->assertTrue($res);
        
       $this->gitlabAdapter->delete('/testing/.gitkeep');
    }
    
    /**
     * @test
     */
    public function it_can_delete_a_directory()
    {
        $this->gitlabAdapter->createDir('/testing', new Config());
        
        $res = $this->gitlabAdapter->deleteDir('/testing');
    
        $this->assertTrue($res);
    
        $res = $this->gitlabAdapter->has('/testing/.gitkeep');
    
        $this->assertFalse($res);
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_a_list_of_contents_of_root()
    {
        $res = $this->gitlabAdapter->listContents();
        
        $this->assertEquals(array_column($res, 'name'), [
            'recursive', 'README.md'
        ]);
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_a_list_of_contents_of_root_recursive()
    {
        $res = $this->gitlabAdapter->listContents('/', true);
        
        $this->assertEquals(array_column($res, 'name'), [
            'recursive', 'README.md', 'recursive.testing.md'
        ]);
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_a_list_of_contents_of_sub_folder()
    {
        $res = $this->gitlabAdapter->listContents('/recursive');
        
        $this->assertEquals(array_column($res, 'name'), [
            'recursive.testing.md'
        ]);
    }
    
    /**
     * @test
     */
    public function it_can_read_a_file()
    {
        $res = $this->gitlabAdapter->read('README.md');
        
        $this->assertStringStartsWith('# Testing repo for `flysystem-gitlab` project', $res['contents']);
    }
    
    /**
     * @test
     */
    public function it_can_not_read_a_file_into_a_stream()
    {
        $this->expectException(LogicException::class);
        
        $this->gitlabAdapter->readStream('README.md');
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_read_failed()
    {
        $this->setInvalidProjectId();
        
        $res = $this->gitlabAdapter->read('README.md');
        
        $this->assertFalse($res);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_metadata()
    {
        $metadata = $this->gitlabAdapter->getMetadata('README.md');
        
        $this->assertArrayHasKey('file_name', $metadata);
        $this->assertArrayHasKey('file_path', $metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('encoding', $metadata);
        $this->assertArrayHasKey('content_sha256', $metadata);
        $this->assertArrayHasKey('ref', $metadata);
        $this->assertArrayHasKey('blob_id', $metadata);
        $this->assertArrayHasKey('commit_id', $metadata);
        $this->assertArrayHasKey('last_commit_id', $metadata);
        $this->assertArrayHasKey('content', $metadata);
        $this->assertArrayHasKey('mimetype', $metadata);
    }
    
    /**
     * @test
     */
    public function it_returns_false_when_retrieving_metadata_failed()
    {
        $this->setInvalidProjectId();
        
        $metadata = $this->gitlabAdapter->getMetadata('README.md');
        
        $this->assertFalse($metadata);
        
        $this->restoreProjectId();
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_size()
    {
        $metadata = $this->gitlabAdapter->getSize('README.md');
        
        $this->assertArrayHasKey('size', $metadata);
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_mimetype()
    {
        $metadata = $this->gitlabAdapter->getMimetype('README.md');
        
        $this->assertArrayHasKey('mimetype', $metadata);
    }
    
    /**
     * @test
     */
    public function it_can_not_retrieve_timestamp()
    {
        $this->expectException(LogicException::class);
        
        $this->gitlabAdapter->getTimestamp('README.md');
    }
    
    /**
     *
     */
    private function setInvalidProjectId()
    {
        $client = $this->gitlabAdapter->getClient();
        $client->setProjectId('123');
        $this->gitlabAdapter->setClient($client);
    }
    
    /**
     *
     */
    private function restoreProjectId()
    {
        $client = $this->gitlabAdapter->getClient();
        $client->setProjectId($this->config[ 'project-id' ]);
        $this->gitlabAdapter->setClient($client);
    }
}