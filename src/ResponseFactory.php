<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Tym\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, $reasonPhrase);
    }
}
