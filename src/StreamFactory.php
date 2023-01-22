<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Tym\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content, ['isText' => true]);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException("The resource is not valid.");
        }
        if (!preg_match("/(r+?|w+|a+|x+|c+)(b|t)?/", stream_get_meta_data($resource)['mode'])) {
            throw new \RuntimeException("The resource must be readable.");
        }

        return new Stream($resource);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new Stream($filename, ['mode' => $mode]);
    }
}
