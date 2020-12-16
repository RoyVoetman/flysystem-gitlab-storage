<?php

namespace RoyVoetman\FlysystemGitlab;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;

/**
 * Class GitlabAdapter
 *
 * @package RoyVoetman\FlysystemGitlab
 */
class Client
{
    const VERSION_URI = "/api/v4";
    
    /**
     * @var ?string
     */
    protected $personalAccessToken;
    
    /**
     * @var string
     */
    protected $projectId;
    
    /**
     * @var string
     */
    protected $branch;
    
    /**
     * @var string
     */
    protected $baseUrl;
    
    /**
     * Client constructor.
     *
     * @param  string  $projectId
     * @param  string  $branch
     * @param  string  $baseUrl
     * @param  string|null  $personalAccessToken
     */
    public function __construct(string $projectId, string $branch, string $baseUrl, ?string $personalAccessToken = null)
    {
        $this->projectId = $projectId;
        $this->branch = $branch;
        $this->baseUrl = $baseUrl;
        $this->personalAccessToken = $personalAccessToken;
    }

    /**
     * @param $path
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function head($path)
    {
        $path = urlencode($path);

        $response = $this->request('HEAD', "files/$path");

        $headers = $response->getHeaders();
        $headers = array_filter(
            $headers,
            function ($key) {
                return substr($key, 0, 9) == 'X-Gitlab-';
            },
            ARRAY_FILTER_USE_KEY
        );

        return $headers;
    }
    
    /**
     * @param $path
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function readRaw(string $path): string
    {
        $path = urlencode($path);
    
        $response = $this->request('GET', "files/$path/raw");
    
        return $this->responseContents($response, false);
    }
    
    /**
     * @param $path
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function read($path)
    {
        $path = urlencode($path);

        $response = $this->request('HEAD', "files/$path");

        $headers = $response->getHeaders();
        $headers = array_filter(
            $headers,
            function ($key) {
                return substr($key, 0, 9) == 'X-Gitlab-';
            },
            ARRAY_FILTER_USE_KEY
        );

        $keys = array_keys($headers);
        $values = array_values($headers);

        array_walk(
            $keys,
            function(&$key) {
                $key = substr($key, 9);
                $key = strtolower($key);
                $key = preg_replace_callback(
                    '/[-_]+(.)?/i',
                    function($matches) {
                        return strtoupper($matches[1]);
                    },
                    $key
                );
            }
        );

        return array_combine($keys, $values);
    }
    
    /**
     * @param $path
     *
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function blame($path)
    {
        $path = urlencode($path);
        
        $response = $this->request('GET', "files/$path/blame");
        
        return $this->responseContents($response);
    }
    
    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  string  $commitMessage
     * @param  bool  $override
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function upload(string $path, string $contents, string $commitMessage, $override = false): array
    {
        $path = urlencode($path);
    
        $method = $override ? 'PUT' : 'POST';
    
        $response = $this->request($method, "files/$path", [
            'content'        => $contents,
            'commit_message' => $commitMessage
        ]);
        
        return $this->responseContents($response);
    }
    
    /**
     * @param  string  $path
     * @param $resource
     * @param  string  $commitMessage
     * @param  bool  $override
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function uploadStream(string $path, $resource, string $commitMessage, $override = false): array
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.',
                gettype($resource)));
        }
    
        return $this->upload($path, stream_get_contents($resource), $commitMessage, $override);
    }
    
    /**
     * @param  string  $path
     * @param  string  $commitMessage
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string $path, string $commitMessage)
    {
        $path = urlencode($path);
        
        $this->request('DELETE', "files/$path", [
            'commit_message' => $commitMessage
        ]);
    }
    
    /**
     * @param  string|null  $directory
     * @param  bool  $recursive
     *
     * @return iterable
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function tree(string $directory = null, bool $recursive = false): iterable
    {
        if ($directory === '/' || $directory === '') {
            $directory = null;
        }
        
        $page = 1;
        
        do {
            $response = $this->request('GET', 'tree', [
                'path'      => $directory,
                'recursive' => $recursive,
                'per_page'  => 100,
                'page'      => $page++
            ]);
    
            yield $this->responseContents($response);
        } while ($this->responseHasNextPage($response));
    }
    
    /**
     * @return string
     */
    public function getPersonalAccessToken(): string
    {
        return $this->personalAccessToken;
    }
    
    /**
     * @param  string  $personalAccessToken
     */
    public function setPersonalAccessToken(string $personalAccessToken)
    {
        $this->personalAccessToken = $personalAccessToken;
    }
    
    /**
     * @return string
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }
    
    /**
     * @param  string  $projectId
     */
    public function setProjectId(string $projectId)
    {
        $this->projectId = $projectId;
    }
    
    /**
     * @return string
     */
    public function getBranch(): string
    {
        return $this->branch;
    }
    
    /**
     * @param  string  $branch
     */
    public function setBranch(string $branch)
    {
        $this->branch = $branch;
    }
    
    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $params
     *
     * @return \GuzzleHttp\Psr7\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $method, string $uri, array $params = []): Response
    {
        $uri = !in_array($method, ['POST', 'PUT', 'DELETE']) ? $this->buildUri($uri, $params) : $this->buildUri($uri);
        $params = in_array($method, ['POST', 'PUT', 'DELETE']) ? ['form_params' => array_merge(['branch' => $this->branch], $params)] : [];

        $client = new HttpClient(['headers' => ['PRIVATE-TOKEN' => $this->personalAccessToken]]);

        return $client->request($method, $uri, $params);
    }
    
    /**
     * @param  string  $uri
     * @param $params
     *
     * @return string
     */
    private function buildUri(string $uri, array $params = []): string
    {
        $params = array_merge(['ref' => $this->branch], $params);
        
        $params = array_map('urlencode', $params);
        
        if(isset($params['path'])) {
            $params['path'] = urldecode($params['path']);
        }
        
        $params = http_build_query($params);
        
        $params = !empty($params) ? "?$params" : null;
    
        $baseUrl = rtrim($this->baseUrl, '/').self::VERSION_URI;
    
        return "{$baseUrl}/projects/{$this->projectId}/repository/{$uri}{$params}";
    }
    
    /**
     * @param  \GuzzleHttp\Psr7\Response  $response
     * @param  bool  $json
     *
     * @return mixed|string
     */
    private function responseContents(Response $response, $json = true)
    {
        $contents = $response->getBody()
            ->getContents();
        
        return ($json) ? json_decode($contents, true) : $contents;
    }
    
    /**
     * @param  \GuzzleHttp\Psr7\Response  $response
     *
     * @return bool
     */
    private function responseHasNextPage(Response $response)
    {
        if ($response->hasHeader('X-Next-Page')) {
            return !empty($response->getHeader('X-Next-Page')[0] ?? "");
        }
        
        return false;
    }
}
