<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Tym\Http\Message\Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (!is_string($uri) && !($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException("The uri must be a string or an instance of " . UriInterface::class);
        }

        return new Request($method, $uri);
    }
}
