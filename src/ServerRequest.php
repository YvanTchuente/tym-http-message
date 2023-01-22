<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * Server parameters.
     * 
     * @var string[]
     */
    private array $serverParams;

    /**
     * Cookie parameters.
     */
    private array $cookieParams;

    /**
     * The URI query parameters.
     * 
     * @var string[]
     */
    private array $queryParams = [];

    /**
     * The deserialized body parameters.
     */
    private array|object|null $parsedBody = null;

    /**
     * The request attributes.
     */
    private array $attributes;

    /**
     * The list of uploaded files.
     * 
     * @var UploadedFileInterface[]
     */
    private array $uploadedFiles = [];

    /**
     * @param string $method The HTTP method.
     * @param UriInterface|string $uri The request URI. 
     * @param array $serverParams Server parameters.
     * @param string $version The HTTP protocol version.
     * @param string[][] $headers The request header values.
     * @param StreamInterface $body The body of the request.
     * @param array $attributes The request attributes.
     * 
     * @throws \InvalidArgumentException For any invalid argument
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $serverParams = [],
        array $cookieParams = [],
        string $version = "HTTP/1.1",
        array $headers = [],
        StreamInterface $body = null,
        array $attributes = []
    ) {
        parent::__construct($method, $uri, $version, $headers, $body);

        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->attributes = $attributes;

        // Set the query parameters
        if ($query = $this->uri->getQuery()) {
            if ($query[0] == '?') {
                $query = substr($query, 1);
            }
            foreach (explode('&', $query) as $key => $value) {
                $params[$key] = $value;
            }
            $this->queryParams = $params;
        }
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies)
    {
        $instance = clone $this;

        $instance->cookieParams = $cookies;

        return $instance;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $instance = clone $this;

        $instance->queryParams = $query;

        return $instance;
    }

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $instance = clone $this;

        array_map(function ($uploadedFile) {
            if (!($uploadedFile instanceof UploadedFileInterface)) {
                throw new \InvalidArgumentException("The array must contain only instances of " . UploadedFileInterface::class);
            }
        }, array_values($uploadedFiles));
        $instance->uploadedFiles = $uploadedFiles;

        return $instance;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        if (isset($data) and (!is_array($data) and !is_object($data))) {
            throw new \InvalidArgumentException("The data is not valid.");
        }

        $instance = clone $this;
        $instance->parsedBody = $data;

        return $instance;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value)
    {
        $instance = clone $this;

        $instance->attributes[$name] = $value;

        return $instance;
    }

    public function withoutAttribute($name)
    {
        $instance = clone $this;

        if (isset($this->attributes[$name])) {
            unset($instance->attributes[$name]);
        }

        return $instance;
    }
}
