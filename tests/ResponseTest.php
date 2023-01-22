<?php

declare(strict_types=1);

namespace Tests;

use Tym\Http\Message\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ResponseTest extends TestCase
{
    private ResponseInterface $response;

    public function setUp(): void
    {
        $this->response = new Response();
    }

    public function testGetStatusCode()
    {
        $this->assertSame(200, $this->response->getStatusCode());
    }

    public function testWithStatus()
    {
        $response = $this->response->withStatus(404, 'Not found');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not found', $response->getReasonPhrase());
    }

    public function testGetReasonPhrase()
    {
        $this->assertSame('OK', $this->response->getReasonPhrase());
    }
}
