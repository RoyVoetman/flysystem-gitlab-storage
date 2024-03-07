<?php

namespace RoyVoetman\FlysystemGitlab\Tests;

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\Attributes\Test;
use RoyVoetman\FlysystemGitlab\Client;

class ClientTest extends TestCase
{
    protected Client $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getClientInstance();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Client::class, $this->getClientInstance());
    }

    #[Test]
    public function it_can_read_a_file()
    {
        $meta = $this->client->read('README.md');

        $this->assertArrayHasKey('ref', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('lastCommitId', $meta);
    }

    #[Test]
    public function it_can_read_a_file_raw()
    {
        $content = $this->client->readRaw('README.md');

        $this->assertStringStartsWith('# Testing repo for `flysystem-gitlab`', $content);
    }

    #[Test]
    public function it_can_create_a_file()
    {
        $contents = $this->client->upload('testing.md', '# Testing create', 'Created file');

        $this->assertStringStartsWith('# Testing create', $this->client->readRaw('testing.md'));
        $this->assertSame($contents, [
                'file_path' => 'testing.md',
                'branch'    => $this->client->getBranch()
            ]);
    }

    #[Test]
    public function it_can_update_a_file()
    {
        $contents = $this->client->upload('testing.md', '# Testing update', 'Updated file', true);

        $this->assertStringStartsWith('# Testing update', $this->client->readRaw('testing.md'));
        $this->assertSame($contents, [
                'file_path' => 'testing.md',
                'branch'    => $this->client->getBranch()
            ]);
    }

    #[Test]
    public function it_can_delete_a_file()
    {
        $this->client->delete('testing.md', 'Deleted file');

        $this->expectException(ClientException::class);

        $this->client->read('testing.md');
    }

    #[Test]
    public function it_can_create_a_file_from_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');

        $contents = $this->client->uploadStream('testing.txt', $stream, 'Created file');

        fclose($stream);

        $this->assertStringStartsWith('File for testing file streams', $this->client->readRaw('testing.txt'));
        $this->assertSame($contents, [
                'file_path' => 'testing.txt',
                'branch'    => $this->client->getBranch()
            ]);

        // Clean up
        $this->client->delete('testing.txt', 'Deleted file');
    }

    #[Test]
    public function it_can_not_a_create_file_from_stream_without_a_valid_stream()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->client->uploadStream('testing.txt', 'string of data', 'Created file');
    }

    #[Test]
    public function it_can_retrieve_a_file_tree()
    {
        $contents = $this->client->tree();
    
        $content = $contents->current();

        $this->assertIsArray($content);
        $this->assertArrayHasKey('id', $content[0]);
        $this->assertArrayHasKey('name', $content[0]);
        $this->assertArrayHasKey('type', $content[0]);
        $this->assertArrayHasKey('path', $content[0]);
        $this->assertArrayHasKey('mode', $content[0]);
    }

    #[Test]
    public function it_can_retrieve_a_file_tree_recursive()
    {
        $contents = $this->client->tree('/', true);
    
        $content = $contents->current();

        $this->assertIsArray($content);
    }

    #[Test]
    public function it_can_retrieve_a_file_tree_of_a_subdirectory()
    {
        $contents = $this->client->tree('recursive', true);
    
        $content = $contents->current();

        $this->assertIsArray($content);
        $this->assertArrayHasKey('id', $content[0]);
        $this->assertArrayHasKey('name', $content[0]);
        $this->assertArrayHasKey('type', $content[0]);
        $this->assertArrayHasKey('path', $content[0]);
        $this->assertArrayHasKey('mode', $content[0]);
    }

    #[Test]
    public function it_can_change_the_branch()
    {
        $this->client->setBranch('dev');

        $this->assertEquals('dev', $this->client->getBranch());
    }

    #[Test]
    public function it_can_change_the_project_id()
    {
        $this->client->setProjectId('12345678');

        $this->assertEquals('12345678', $this->client->getProjectId());
    }

    #[Test]
    public function it_can_change_the_personal_access_token()
    {
        $this->client->setPersonalAccessToken('12345678');

        $this->assertEquals('12345678', $this->client->getPersonalAccessToken());
    }
}
