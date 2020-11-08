<?php

namespace RoyVoetman\FlysystemGitlab\Tests;

use GuzzleHttp\Exception\ClientException;
use RoyVoetman\FlysystemGitlab\Client;

class ClientTest extends TestCase
{
    /**
     * @var \RoyVoetman\FlysystemGitlab\Client
     */
    protected $client;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getClientInstance();
    }

    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Client::class, $this->getClientInstance());
    }

    /**
     * @test
     */
    public function it_can_read_a_file()
    {
        $file = $this->client->read('README.md');

        $this->assertStringStartsWith(base64_encode('# Testing repo for `flysystem-gitlab` project'),
            $file[ 'content' ]);
    }

    /**
     * @test
     */
    public function it_can_read_a_file_raw()
    {
        $content = $this->client->readRaw('README.md');

        $this->assertStringStartsWith('# Testing repo for `flysystem-gitlab` project', $content);
    }

    /**
     * @test
     */
    public function it_can_create_a_file()
    {
        $contents = $this->client->upload('testing.md', '# Testing create', 'Created file');

        $this->assertStringStartsWith('# Testing create', $this->client->readRaw('testing.md'));
        $this->assertTrue($contents === [
                'file_path' => 'testing.md',
                'branch'    => $this->client->getBranch()
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_a_file()
    {
        $contents = $this->client->upload('testing.md', '# Testing update', 'Updated file', true);

        $this->assertStringStartsWith('# Testing update', $this->client->readRaw('testing.md'));
        $this->assertTrue($contents === [
                'file_path' => 'testing.md',
                'branch'    => $this->client->getBranch()
            ]);
    }

    /**
     * @test
     */
    public function it_can_delete_a_file()
    {
        $this->client->delete('testing.md', 'Deleted file');

        $this->expectException(ClientException::class);

        $this->client->read('testing.md');
    }

    /**
     * @test
     */
    public function it_can_create_a_file_from_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');

        $contents = $this->client->uploadStream('testing.txt', $stream, 'Created file');

        fclose($stream);

        $this->assertStringStartsWith('File for testing file streams', $this->client->readRaw('testing.txt'));
        $this->assertTrue($contents === [
                'file_path' => 'testing.txt',
                'branch'    => $this->client->getBranch()
            ]);

        // Clean up
        $this->client->delete('testing.txt', 'Deleted file');
    }

    /**
     * @test
     */
    public function it_can_not_a_create_file_from_stream_without_a_valid_stream()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->client->uploadStream('testing.txt', 'string of data', 'Created file');
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_file_tree()
    {
        $contents = $this->client->tree();

        $this->assertTrue(isset($contents[ 0 ]));
        $this->assertTrue(count($contents) == 5);
        $this->assertArrayHasKey('id', $contents[ 0 ]);
        $this->assertArrayHasKey('name', $contents[ 0 ]);
        $this->assertArrayHasKey('type', $contents[ 0 ]);
        $this->assertArrayHasKey('path', $contents[ 0 ]);
        $this->assertArrayHasKey('mode', $contents[ 0 ]);
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_file_tree_recursive()
    {
        $contents = $this->client->tree('/', true);

        $this->assertTrue(isset($contents[ 0 ]));
        $this->assertTrue(count($contents) == 6);
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_file_tree_of_a_subdirectory()
    {
        $contents = $this->client->tree('recursive', true);

        $this->assertTrue(isset($contents[ 0 ]));
        $this->assertTrue(count($contents) == 1);
        $this->assertArrayHasKey('id', $contents[ 0 ]);
        $this->assertArrayHasKey('name', $contents[ 0 ]);
        $this->assertArrayHasKey('type', $contents[ 0 ]);
        $this->assertArrayHasKey('path', $contents[ 0 ]);
        $this->assertArrayHasKey('mode', $contents[ 0 ]);
    }

    /**
     * @test
     */
    public function it_can_change_the_branch()
    {
        $this->client->setBranch('dev');

        $this->assertEquals($this->client->getBranch(), 'dev');
    }

    /**
     * @test
     */
    public function it_can_change_the_project_id()
    {
        $this->client->setProjectId('12345678');

        $this->assertEquals($this->client->getProjectId(), '12345678');
    }

    /**
     * @test
     */
    public function it_can_change_the_personal_access_token()
    {
        $this->client->setPersonalAccessToken('12345678');

        $this->assertEquals($this->client->getPersonalAccessToken(), '12345678');
    }
}
