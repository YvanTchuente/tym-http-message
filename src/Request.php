<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class Request extends Message implements RequestInterface
{
    /**
     * Valid HTTP methods.
     */
    protected const VALID_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT', 'OPTIONS'];

    /**
     * The HTTP method.
     */
    protected string $method;

    /**
     * The request URI.
     */
    protected UriInterface $uri;

    /**
     * The request target.
     */
    protected string $target;

    /**
     * @param string $method The HTTP method.
     * @param UriInterface|string $uri The request URI.
     * @param string $version The HTTP protocol version.
     * @param string[][] $headers The request header values.
     * @param StreamInterface $body The body of the request.
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        string $version = "HTTP/1.1",
        array $headers = [],
        StreamInterface $body = null
    ) {
        $this->protocolVersion = $this->validateVersion($version);

        $this->method = $this->validateMethod($method);

        $this->uri = (is_string($uri)) ? new Uri($uri) : $uri;

        $this->target = $this->getTarget($this->uri, $method);

        array_walk(
            $headers,
            function ($value, $name) {
                $this->validateHeader($name, $value);
            }
        );
        $this->headers['Host'] = preg_split('/\n/', $this->uri->getHost());

        $this->body = $body ?? new Stream('', ['isText' => true]);
    }

    public function getRequestTarget()
    {
        return $this->target;
    }

    public function withRequestTarget($requestTarget)
    {
        if (!is_string($requestTarget)) {
            throw new \InvalidArgumentException("The request target must be a string.");
        }

        $instance = clone $this;
        $instance->target = $requestTarget;

        return $instance;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $instance = clone $this;

        $instance->method = $this->validateMethod($method);

        return $instance;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $instance = clone $this;

        $instance->uri = $uri;
        if ($preserveHost) {
            if ($this->hasHeader('Host') && !($host = $uri->getHost())) {
                $instance = $instance->withHeader('Host', $host);
            }
        }

        return $instance;
    }

    /**
     * Validates a given HTTP method.
     * 
     * @throws \InvalidArgumentException For invalid HTTP methods
     */
    protected function validateMethod(string $method)
    {
        if (!in_array(strtoupper($method), self::VALID_METHODS)) {
            throw new \InvalidArgumentException("Invalid HTTP method");
        }

        return strtoupper($method);
    }

    protected function getTarget(UriInterface $uri, string $method)
    {
        if (!isset($uri)) {
            $target = '/';
        } elseif (preg_match('/connect/i', $method) && $uri->getAuthority()) {
            $target = $uri->getAuthority();
        } elseif ($uri->getPath()) {
            $target = $uri->getPath();
        }

        if (preg_match('/GET/', $method)) {
            $target .= ($uri->getQuery()) ? '?' . $uri->getQuery() : '';
        }

        return $target;
    }
}
