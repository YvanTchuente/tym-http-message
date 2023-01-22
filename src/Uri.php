<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class Uri implements UriInterface
{
    /**
     * URI scheme segment.
     */
    private string $scheme = "";

    /**
     * URI username segment.
     */
    private string $username = "";

    /**
     * URI user password segment.
     */
    private string $password = "";

    /**
     * URI host segment.
     */
    private string $host = "";

    /**
     * URI port segment.
     */
    private ?int $port = null;

    /**
     * URI path segment.
     */
    private string $path = "";

    /**
     * Query string parameters.
     * 
     * @var string[]
     */
    private array $queryParams = [];

    /**
     * URI fragment.
     */
    private string $fragment = "";

    /**
     * @param string $uri The URI to parse.
     * 
     * @throws \LogicException If the URI could not be parsed.
     */
    public function __construct(string $uri = '')
    {
        if (!($components = parse_url($uri))) {
            throw new \LogicException("The URI could not be parsed.");
        }

        foreach ($components as $name => $value) {
            if (!$value || $name == 'query') {
                continue;
            } elseif ($name === 'user') {
                $name = 'username';
            } elseif ($name === 'pass') {
                $name = 'password';
            }

            $this->{$name} = $value;
        }

        if (isset($components['query'])) {
            $this->queryParams = $this->getParameters($components['query']);
        }

        if (!$this->port && $this->scheme) {
            if ($port = $this->getPortForScheme($this->scheme)) {
                $this->port = $port;
            }
        }
    }

    public function getScheme()
    {
        return strtolower($this->scheme);
    }

    public function getAuthority()
    {
        $authority = '';
        if ($this->getUserInfo()) {
            $authority .= $this->getUserInfo() . '@';
        }

        $authority .= $this->host;

        if ($this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo()
    {
        if (!$this->username) {
            return '';
        }

        if ($this->password) {
            return $this->username . ":" . $this->password;
        }

        return $this->username;
    }

    public function getHost()
    {
        return strtolower($this->host);
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return implode("/", array_map("rawurlencode", explode('/', $this->path)));
    }

    public function getQuery()
    {
        if (!$this->queryParams) {
            return '';
        }

        $query = "";
        foreach ($this->queryParams as $name => $value) {
            $query .= rawurlencode($name);

            if ($value) {
                $query .= "=" . rawurlencode($value);
            }

            $query .= "&";
        }
        $query = substr($query, 0, -1);

        return $query;
    }

    public function getFragment()
    {
        return rawurlencode($this->fragment);
    }

    public function withScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException("The scheme must be a string.");
        }

        $instance = clone $this;

        if (!$scheme and $this->getScheme()) {
            $instance->scheme = "";
        } else {
            if (preg_match('/https?|ssh|ftp/i', $scheme)) {
                $instance->scheme = $scheme;

                if (!$instance->port) {
                    if ($port = $this->getPortForScheme($scheme)) {
                        $instance->port = $port;
                    }
                }
            } else {
                throw new \InvalidArgumentException("[$scheme] is not a supported scheme.");
            }
        }

        return $instance;
    }

    public function withUserInfo($user, $password = null)
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException("The user name must be a string.");
        }
        if (!is_null($password) and !is_string($password)) {
            throw new \InvalidArgumentException("The password must be a string.");
        }

        $instance = clone $this;

        if (!$user and $this->getUserInfo()) {
            $instance->username = "";
        } else {
            $instance->username = $user;

            if ($password) {
                $instance->password = $password;
            }
        }

        return $instance;
    }

    public function withHost($host)
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException("The hostname must be a string.");
        }

        $instance = clone $this;

        if (!$host and $this->getHost()) {
            $instance->host = "";
        } else {
            if (preg_match('/\w{5,9}|(\d{1,3}(\b|\.)){4}|\w{3}\.\w+\.\w{2}/', strtolower($host))) {
                $instance->host = $host;
            } else {
                throw new \InvalidArgumentException("[$host] is not a valid hostname.");
            }
        }

        return $instance;
    }

    public function withPort($port = null)
    {
        if (!is_null($port) and !is_int($port)) {
            throw new \InvalidArgumentException("The port must be an integer.");
        }

        $instance = clone $this;

        if (!$port) {
            $instance->port = null;
        } else {
            if (in_array($port, range(1, 10000))) {
                $instance->port = $port;
            } else {
                throw new \InvalidArgumentException("[$port] is not a valid port.");
            }
        }

        return $instance;
    }

    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException("The path must be a string.");
        }

        $instance = clone $this;

        if (!preg_match('/\/?(\.+\/?)?/', $path)) {
            throw new \InvalidArgumentException("[$path] is not valid.");
        }
        $instance->path = $path;

        return $instance;
    }

    public function withQuery($query)
    {
        $instance = clone $this;

        if (!$query and $this->getQuery()) {
            $instance->queryParams = $this->getParameters($query);
        } else {
            if (preg_match('/(\w+(=\w)?&?)+/', $query)) {
                $instance->queryParams = $this->getParameters($query);
            } else {
                throw new \InvalidArgumentException("[$query] is not a valid query string.");
            }
        }

        return $instance;
    }

    public function withFragment($fragment)
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException("The fragment must be a string.");
        }

        $instance = clone $this;

        if (!$fragment and $this->getFragment()) {
            $instance->fragment = "";
        } else {
            $instance->fragment = $fragment;
        }

        return $instance;
    }

    public function __toString()
    {
        $uri = ($this->getScheme()) ? $this->getScheme() . '://' : '';

        if ($this->getAuthority()) {
            $uri .= $this->getAuthority();
        } else {
            $uri .= ($this->getHost()) ? $this->getHost() : '';
            $uri .= ($this->getPort()) ? ':' . $this->getPort() : '';
        }

        if ($path = $this->getPath()) {
            if ($path[0] != '/') {
                $uri .= '/' . $path;
            } else {
                $uri .= $path;
            }
        }

        $uri .= ($this->getQuery()) ? '?' . $this->getQuery() : '';
        $uri .= ($this->getFragment()) ? '#' . $this->getFragment() : '';

        return $uri;
    }

    /**
     * Retrieves the parameters of a given query string.
     * 
     * @return string[]
     */
    private function getParameters(string $query)
    {
        $params = [];
        foreach (explode('&', $query) as $pair) {
            $elements = explode('=', $pair);

            if (count($elements) == 2) {
                $params[$elements[0]] = $elements[1];
            } else {
                $params[$elements[0]] = null;
            }
        }

        return $params;
    }

    /**
     * Get the port number equivalent to a given scheme.
     * 
     * @return int|null
     */
    private function getPortForScheme(string $scheme)
    {
        switch (true) {
            case (preg_match('/http/i', $scheme)):
                $port = 80;
                break;
            case (preg_match('/https/i', $scheme)):
                $port = 443;
                break;
            case (preg_match('/ftp/i', $scheme)):
                $port = 21;
                break;
            case (preg_match('/ssh/i', $scheme)):
                $port = 22;
                break;
        }

        return $port ?? null;
    }
}
