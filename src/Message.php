<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
abstract class Message implements MessageInterface
{
    /**
     * The HTTP protocol version number.
     */
    protected string $protocolVersion;

    /**
     * The message header values.
     * 
     * @var string[][]
     */
    protected array $headers = [];

    /**
     * The body of the message.
     */
    protected StreamInterface $body;

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        if (!is_string($version)) {
            throw new \InvalidArgumentException("The version must be a string.");
        }

        $instance = clone $this;
        $instance->protocolVersion = $this->validateVersion($version);

        return $instance;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return (bool) $this->findHeader($name);
    }

    public function getHeader($name)
    {
        return $this->findHeader($name) ?? [];
    }

    public function getHeaderLine($name)
    {
        if ($values = $this->findHeader($name)) {
            $headerLine = implode(",", $values);
        }
        return $headerLine ?? "";
    }

    public function withHeader($name, $value)
    {
        $this->validateHeader($name, $value);
        $instance = clone $this;

        if (is_array($value)) {
            $instance->headers[$name] = $value;
        } else if (preg_match('/((.+),|;)+/', $value) && !preg_match('/Date|Expires/i', $name)) {
            $instance->headers[$name] = preg_split('/,|;/', $value);
        } else {
            $instance->headers[$name] = preg_split('/\n/', $value);
        }

        return $instance;
    }

    public function withAddedHeader($name, $value)
    {
        $this->validateHeader($name, $value);
        $instance = clone $this;

        if (is_array($value)) {
            $instance->headers[$name] = array_merge($instance->headers[$name], $value);
        } else {
            $instance->headers[$name][] = $value;
        }

        return $instance;
    }

    public function withoutHeader($name)
    {
        $instance = clone $this;

        if ($this->findHeader($name)) {
            unset($instance->headers[$name]);
        }

        return $instance;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        if (!$body->isSeekable()) {
            throw new \InvalidArgumentException("The body is not valid.");
        }

        $instance = clone $this;
        $instance->body = $body;

        return $instance;
    }

    /**
     * Validates a given HTTP protocol version.
     * 
     * @return string The protocol version number.
     * 
     * @throws \InvalidArgumentException
     */
    protected function validateVersion(string $version)
    {
        if (!preg_match('/\d\.\d/', $version)) {
            throw new \InvalidArgumentException("$version is missing the protocol version number.");
        }

        return preg_replace('/[^0-9\.]/', '', $version);
    }

    /**
     * Finds a message header value by a given header field name.
     * 
     * @return string[]|null
     */
    protected function findHeader(string $name)
    {
        foreach (array_keys($this->headers) as $header_field_name) {
            if (preg_match("/^$name$/i", $header_field_name)) {
                return $this->headers[$name];
            }
        }

        return null;
    }

    /**
     * Validates a given header field name and value(s).
     * 
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    protected function validateHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException("The header field name must be a string");
        }
        if (!is_string($value) && !is_array($value)) {
            throw new \InvalidArgumentException("The header value(s) must a string or an array.");
        }
        if (is_array($value)) {
            array_map(function ($element) {
                if (!is_string($element)) {
                    throw new \InvalidArgumentException("[$element] is not a valid header value.");
                }
            }, $value);
        }
    }
}
