<?php

namespace RoyVoetman\FlysystemGitlab\Tests;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\Attributes\Test;
use RoyVoetman\FlysystemGitlab\Client;
use RoyVoetman\FlysystemGitlab\GitlabAdapter;

class GitlabAdapterTest extends TestCase
{
    protected GitlabAdapter $gitlabAdapter;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->gitlabAdapter = $this->getAdapterInstance();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(GitlabAdapter::class, $this->getAdapterInstance());
    }

    #[Test]
    public function it_can_retrieve_client_instance()
    {
        $this->assertInstanceOf(Client::class, $this->gitlabAdapter->getClient());
    }

    #[Test]
    public function it_can_set_client_instance()
    {
        $this->setInvalidProjectId();

        $this->assertEquals($this->gitlabAdapter->getClient()
            ->getProjectId(), '123');
    }

    #[Test]
    public function it_can_read_a_file()
    {
        $response = $this->gitlabAdapter->read('README.md');

        $this->assertStringStartsWith('# Testing repo for `flysystem-gitlab`', $response);
    }

    #[Test]
    public function it_can_read_a_file_into_a_stream()
    {
        $stream = $this->gitlabAdapter->readStream('README.md');

        $this->assertIsResource($stream);
        $this->assertEquals(stream_get_contents($stream, -1, 0), $this->gitlabAdapter->read('README.md'));
    }

    #[Test]
    public function it_throws_when_read_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToReadFile::class);

        $this->gitlabAdapter->read('README.md');
    }

    #[Test]
    public function it_can_determine_if_a_project_has_a_file()
    {
        $this->assertTrue($this->gitlabAdapter->fileExists('/README.md'));

        $this->assertFalse($this->gitlabAdapter->fileExists('/I_DONT_EXIST.md'));
    }

    #[Test]
    public function it_throws_when_file_existence_failed()
    {
        $this->setInvalidToken();

        $this->expectException(UnableToCheckFileExistence::class);

        $this->gitlabAdapter->fileExists('/README.md');
    }

    #[Test]
    public function it_can_delete_a_file()
    {
        $this->gitlabAdapter->write('testing.md', '# Testing create', new Config());

        $this->assertTrue($this->gitlabAdapter->fileExists('/testing.md'));

        $this->gitlabAdapter->delete('/testing.md');

        $this->assertFalse($this->gitlabAdapter->fileExists('/testing.md'));
    }

    #[Test]
    public function it_returns_false_when_delete_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToDeleteFile::class);

        $this->gitlabAdapter->delete('testing_renamed.md');
    }

    #[Test]
    public function it_can_write_a_new_file()
    {
        $this->gitlabAdapter->write('testing.md', '# Testing create', new Config());

        $this->assertTrue($this->gitlabAdapter->fileExists('testing.md'));
        $this->assertEquals('# Testing create', $this->gitlabAdapter->read('testing.md'));

        $this->gitlabAdapter->delete('testing.md');
    }

    #[Test]
    public function it_automatically_creates_missing_directories()
    {
        $this->gitlabAdapter->write('/folder/missing/testing.md', '# Testing create folders', new Config());

        $this->assertTrue($this->gitlabAdapter->fileExists('/folder/missing/testing.md'));
        $this->assertEquals('# Testing create folders', $this->gitlabAdapter->read('/folder/missing/testing.md'));

        $this->gitlabAdapter->delete('/folder/missing/testing.md');
    }

    #[Test]
    public function it_throws_when_write_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToWriteFile::class);

        $this->gitlabAdapter->write('testing.md', '# Testing create', new Config());
    }

    #[Test]
    public function it_can_write_a_file_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);

        $this->assertTrue($this->gitlabAdapter->fileExists('testing.txt'));
        $this->assertEquals('File for testing file streams', $this->gitlabAdapter->read('testing.txt'));

        $this->gitlabAdapter->delete('testing.txt');
    }

    #[Test]
    public function it_throws_when_writing_file_stream_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToWriteFile::class);

        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);
    }

    #[Test]
    public function it_can_override_a_file()
    {
        $this->gitlabAdapter->write('testing.md', '# Testing create', new Config());
        $this->gitlabAdapter->write('testing.md', '# Testing update', new Config());

        $this->assertStringStartsWith($this->gitlabAdapter->read('testing.md'), '# Testing update');

        $this->gitlabAdapter->delete('testing.md');
    }

    #[Test]
    public function it_can_override_with_a_file_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);

        $stream = fopen(__DIR__.'/assets/testing-update.txt', 'r+');
        $this->gitlabAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);

        $this->assertTrue($this->gitlabAdapter->fileExists('testing.txt'));
        $this->assertEquals('File for testing file streams!', $this->gitlabAdapter->read('testing.txt'));

        $this->gitlabAdapter->delete('testing.txt');
    }

    #[Test]
    public function it_can_move_a_file()
    {
        $this->gitlabAdapter->write('testing.md', '# Testing move', new Config());

        $this->gitlabAdapter->move('testing.md', 'testing_move.md', new Config());

        $this->assertFalse($this->gitlabAdapter->fileExists('testing.md'));
        $this->assertTrue($this->gitlabAdapter->fileExists('testing_move.md'));

        $this->assertEquals('# Testing move', $this->gitlabAdapter->read('testing_move.md'));

        $this->gitlabAdapter->delete('testing_move.md');
    }

    #[Test]
    public function it_throws_when_move_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToMoveFile::class);

        $this->gitlabAdapter->move('testing_move.md', 'testing.md', new Config());
    }

    #[Test]
    public function it_can_copy_a_file()
    {
        $this->gitlabAdapter->write('testing.md', '# Testing copy', new Config());

        $this->gitlabAdapter->copy('testing.md', 'testing_copy.md', new Config());

        $this->assertTrue($this->gitlabAdapter->fileExists('testing.md'));
        $this->assertTrue($this->gitlabAdapter->fileExists('testing_copy.md'));

        $this->assertEquals($this->gitlabAdapter->read('testing.md'), '# Testing copy');
        $this->assertEquals($this->gitlabAdapter->read('testing_copy.md'), '# Testing copy');

        $this->gitlabAdapter->delete('testing.md');
        $this->gitlabAdapter->delete('testing_copy.md');
    }

    #[Test]
    public function it_throws_when_copy_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToCopyFile::class);

        $this->gitlabAdapter->copy('testing_copy.md', 'testing.md', new Config());
    }

    #[Test]
    public function it_can_create_a_directory()
    {
        $this->gitlabAdapter->createDirectory('/testing', new Config());

        $this->assertTrue($this->gitlabAdapter->fileExists('/testing/.gitkeep'));

        $this->gitlabAdapter->delete('/testing/.gitkeep');
    }

    #[Test]
    public function it_can_retrieve_a_list_of_contents_of_root()
    {
        $list = $this->gitlabAdapter->listContents('/', false);
        $expectedPaths = [
            ['type' => 'dir', 'path' => 'recursive'],
            ['type' => 'file', 'path' => 'LICENSE'],
            ['type' => 'file', 'path' => 'README.md'],
            ['type' => 'file', 'path' => 'test'],
            ['type' => 'file', 'path' => 'test2'],
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertContains(
                ['type' => $item['type'], 'path' => $item['path']], $expectedPaths
            );
        }
    }

    #[Test]
    public function it_can_retrieve_a_list_of_contents_of_root_recursive()
    {
        $list = $this->gitlabAdapter->listContents('/', true);
        $expectedPaths = [
            ['type' => 'dir', 'path' => 'recursive'],
            ['type' => 'dir', 'path' => 'recursive/level-1'],
            ['type' => 'dir', 'path' => 'recursive/level-1/level-2'],
            ['type' => 'file', 'path' => 'LICENSE'],
            ['type' => 'file', 'path' => 'README.md'],
            ['type' => 'file', 'path' => 'recursive/recursive.testing.md'],
            ['type' => 'file', 'path' => 'recursive/level-1/level-2/.gitkeep'],
            ['type' => 'file', 'path' => 'test'],
            ['type' => 'file', 'path' => 'test2'],
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertContains(
                ['type' => $item['type'], 'path' => $item['path']], $expectedPaths
            );
        }
    }

    #[Test]
    public function it_can_retrieve_a_list_of_contents_of_sub_folder()
    {
        $list = $this->gitlabAdapter->listContents('/recursive', false);
        $expectedPaths = [
            ['type' => 'dir', 'path' => 'recursive/level-1'],
            ['type' => 'dir', 'path' => 'recursive/level-1/level-2'],
            ['type' => 'file', 'path' => 'recursive/recursive.testing.md'],
            ['type' => 'file', 'path' => 'recursive/level-1/level-2/.gitkeep'],
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertContains(
                ['type' => $item['type'], 'path' => $item['path']], $expectedPaths
            );
        }
    }

    #[Test]
    public function it_can_retrieve_a_list_of_contents_of_deep_sub_folder()
    {
        $list = $this->gitlabAdapter->listContents('/recursive/level-1/level-2', false);
        $expectedPaths = [
            ['type' => 'file', 'path' => 'recursive/level-1/level-2/.gitkeep'],
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertContains(
                ['type' => $item['type'], 'path' => $item['path']], $expectedPaths
            );
        }
    }

    #[Test]
    public function it_can_delete_a_directory()
    {
        $this->gitlabAdapter->createDirectory('/testing', new Config());
        $this->gitlabAdapter->write('/testing/testing.md', 'Testing delete directory', new Config());

        $this->gitlabAdapter->deleteDirectory('/testing');

        $this->assertFalse($this->gitlabAdapter->fileExists('/testing/.gitkeep'));
        $this->assertFalse($this->gitlabAdapter->fileExists('/testing/testing.md'));
    }

    #[Test]
    public function it_throws_when_delete_directory_failed()
    {
        $this->setInvalidProjectId();
        
        $this->expectException(FilesystemException::class);
        
        $this->gitlabAdapter->deleteDirectory('/testing');
    }

    #[Test]
    public function it_can_retrieve_size()
    {
        $size = $this->gitlabAdapter->fileSize('README.md');

        $this->assertInstanceOf(FileAttributes::class, $size);
        $this->assertEquals(37, $size->fileSize());
    }

    #[Test]
    public function it_can_retrieve_mimetype()
    {
        $metadata = $this->gitlabAdapter->mimeType('README.md');

        $this->assertInstanceOf(FileAttributes::class, $metadata);
        $this->assertEquals('text/markdown', $metadata->mimeType());
    }

    #[Test]
    public function it_can_not_retrieve_lastModified()
    {
        $lastModified = $this->gitlabAdapter->lastModified('README.md');

        $this->assertInstanceOf(FileAttributes::class, $lastModified);
        $this->assertEquals(1606750652, $lastModified->lastModified());
    }

    #[Test]
    public function it_throws_when_getting_visibility()
    {
        $this->expectException(UnableToSetVisibility::class);

        $this->gitlabAdapter->visibility('README.md');
    }

    #[Test]
    public function it_throws_when_setting_visibility()
    {
        $this->expectException(UnableToSetVisibility::class);

        $this->gitlabAdapter->setVisibility('README.md', 0777);
    }

    #[Test]
    public function it_can_check_directory_if_exists()
    {
        $dir = 'test-dir/test-dir2/test-dir3';
        $this->gitlabAdapter->createDirectory($dir, new Config());
        $this->assertTrue($this->gitlabAdapter->directoryExists($dir));
        $this->gitlabAdapter->deleteDirectory($dir);
    }

    #[Test]
    public function it_cannot_check_if_directory_exists()
    {
        $this->assertFalse($this->gitlabAdapter->directoryExists('test_non_existent_dir'));
    }
    
    private function setInvalidToken()
    {
        $client = $this->gitlabAdapter->getClient();
        $client->setPersonalAccessToken('123');
        $this->gitlabAdapter->setClient($client);
    }
    
    private function setInvalidProjectId()
    {
        $client = $this->gitlabAdapter->getClient();
        $client->setProjectId('123');
        $this->gitlabAdapter->setClient($client);
    }
}
