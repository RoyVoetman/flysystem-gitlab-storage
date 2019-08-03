<?php

namespace RoyVoetman\FlysystemGitlab;

use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util\MimeType;
use LogicException;

/**
 * Class GitlabAdapter
 *
 * @package RoyVoetman\FlysystemGitlab
 */
class GitlabAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;
    
    const CREATED_FILE_COMMIT_MESSAGE = 'Uploaded file via Gitlab API';
    const UPDATED_FILE_COMMIT_MESSAGE = 'Updated file via Gitlab API';
    const DELETED_FILE_COMMIT_MESSAGE = 'Deleted file via Gitlab API';
    
    /**
     * @var \RoyVoetman\FlysystemGitlab\Client
     */
    protected $client;
    
    /**
     * GitlabAdapter constructor.
     *
     * @param  \RoyVoetman\FlysystemGitlab\Client  $client
     * @param  string  $prefix
     */
    public function __construct(Client $client, $prefix = '')
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }
    
    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  \League\Flysystem\Config  $config
     *
     * @return array|bool|false
     */
    public function write($path, $contents, Config $config)
    {
        try {
            $this->client->upload($this->applyPathPrefix($path), $contents, self::CREATED_FILE_COMMIT_MESSAGE);
    
            return $this->client->read($this->applyPathPrefix($path));
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     * @param  resource  $resource
     * @param  \League\Flysystem\Config  $config
     *
     * @return array|bool|false|mixed|string
     */
    public function writeStream($path, $resource, Config $config)
    {
        try {
            $this->client->uploadStream($this->applyPathPrefix($path), $resource, self::CREATED_FILE_COMMIT_MESSAGE);
        
            return $this->client->read($this->applyPathPrefix($path));
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  \League\Flysystem\Config  $config
     *
     * @return array|bool|false|mixed|string
     */
    public function update($path, $contents, Config $config)
    {
        try {
            $this->client->upload($this->applyPathPrefix($path), $contents, self::UPDATED_FILE_COMMIT_MESSAGE, true);
        
            return $this->client->read($this->applyPathPrefix($path));
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     * @param  resource  $resource
     * @param  \League\Flysystem\Config  $config
     *
     * @return array|bool|false|mixed|string
     */
    public function updateStream($path, $resource, Config $config)
    {
        try {
            $this->client->uploadStream($this->applyPathPrefix($path), $resource, self::UPDATED_FILE_COMMIT_MESSAGE,
                true);
        
            return $this->client->read($this->applyPathPrefix($path));
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     * @param  string  $newPath
     *
     * @return bool
     */
    public function rename($path, $newPath)
    {
        try {
            $contents = $this->client->readRaw($this->applyPathPrefix($path));
        
            $this->client->upload($this->applyPathPrefix($newPath), $contents, self::CREATED_FILE_COMMIT_MESSAGE);
        
            $this->client->delete($this->applyPathPrefix($path), self::DELETED_FILE_COMMIT_MESSAGE);
        
            return true;
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     * @param  string  $newPath
     *
     * @return bool
     */
    public function copy($path, $newPath)
    {
        try {
            $contents = $this->client->readRaw($this->applyPathPrefix($path));
        
            $this->client->upload($this->applyPathPrefix($newPath), $contents, self::CREATED_FILE_COMMIT_MESSAGE);
        
            return true;
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     *
     * @return bool
     */
    public function delete($path)
    {
        try {
            $this->client->delete($this->applyPathPrefix($path), self::DELETED_FILE_COMMIT_MESSAGE);
        
            return true;
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $files = $this->listContents($this->applyPathPrefix($dirname));
        $status = true;
        
        foreach ($files as $file) {
            if ($file[ 'type' ] !== 'tree') {
                try {
                    $this->client->delete($file[ 'path' ], self::DELETED_FILE_COMMIT_MESSAGE);
                } catch (GuzzleException $e) {
                    $status = false;
                }
            }
        }
        
        return $status;
    }
    
    /**
     * @param  string  $dirname
     * @param  \League\Flysystem\Config  $config
     *
     * @return array|false|void
     */
    public function createDir($dirname, Config $config)
    {
        $path = rtrim($dirname, '/') . '/.gitkeep';
    
        $res = $this->write($this->applyPathPrefix($path), '', $config);
    
        return ($res !== false) ? true : false;
    }
    
    /**
     * @param  string  $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        try {
            $this->client->read($this->applyPathPrefix($path));
        } catch (GuzzleException $e) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param  string  $path
     *
     * @return array|bool|false|mixed|string
     */
    public function read($path)
    {
        try {
            $res = $this->client->read($this->applyPathPrefix($path));
            $res['contents'] = base64_decode($res['content']);
            
            return $res;
        } catch (GuzzleException $e) {
            return false;
        }
    }
    
    /**
     * @param  string  $path
     *
     * @return array|false|void
     */
    public function readStream($path)
    {
        throw new LogicException(get_class($this).' Gitlab API does not support reading a file into a stream.');
    }
    
    /**
     * @param  string  $directory
     * @param  bool  $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false): array
    {
        try {
            $res = $this->client->tree($this->applyPathPrefix($directory), $recursive);
    
            return array_map(function($item) {
                $item['type'] = ($item['type'] === 'blob') ? 'file' : $item['type'];
                
                return $item;
            }, $res);
        } catch (GuzzleException $e) {
            return [];
        }
    }
    
    /**
     * @param  string  $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        try {
            $metadata = $this->client->read($this->applyPathPrefix($path));
        } catch (GuzzleException $e) {
            return false;
        }
    
        $metadata[ 'mimetype' ] = MimeType::detectByFilename($path);
    
        return $metadata;
    }
    
    /**
     * @param  string  $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($this->applyPathPrefix($path));
    }
    
    /**
     * @param  string  $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($this->applyPathPrefix($path));
    }
    
    /**
     * @param  string  $path
     *
     * @return array|false|void
     */
    public function getTimestamp($path)
    {
        throw new LogicException(get_class($this).' Gitlab API does not support timestamps.');
    }
    
    /**
     * @return \RoyVoetman\FlysystemGitlab\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
    
    /**
     * @param  \RoyVoetman\FlysystemGitlab\Client  $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}