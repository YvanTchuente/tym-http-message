<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Tym\Http\Message\Uri;
use Tym\Http\Message\Stream;
use Psr\Http\Message\UriInterface;
use Tym\Http\Message\UploadedFile;
use Tym\Http\Message\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (!is_string($uri) && !($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException("The uri must be a string or an instance of " . UriInterface::class);
        }

        return new ServerRequest($method, $uri, $serverParams);
    }

    /**
     * Create a server request with data from superglobal variables
     */
    public static function createFromGlobals(): ServerRequestInterface
    {
        $serverRequest = new ServerRequest(self::getGlobalRequestMethod(), self::getGlobalRequestUri(), $_SERVER, $_COOKIE);
        $serverRequest = $serverRequest
            ->withParsedBody(self::getGlobalParsedBody())
            ->withQueryParams(self::getGlobalQueryParams())
            ->withUploadedFiles(self::getGlobalUploadedFiles());
        $serverRequest = self::setGlobalRequestHeaders($serverRequest);

        return $serverRequest;
    }

    private static function getGlobalRequestMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    private static function getGlobalRequestUri()
    {
        return new Uri($_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

    private static function setGlobalRequestHeaders(ServerRequestInterface $serverRequest)
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = self::altApacheRequestHeaders();
        }

        foreach ($headers as $name => $value) {
            $values = preg_split("/,|;/", $value);
            $serverRequest = $serverRequest->withHeader($name, $values);
        }

        return $serverRequest;
    }

    private static function getGlobalParsedBody()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? 'application/octet-stream';

        if (preg_match('/application\/x-www-form-urlencoded|multipart\/form-data/', $contentType)) {
            $parsedBody = $_POST;
        } else if (preg_match('/application\/(.+)?json/', $contentType)) {
            $body = new Stream("php://input");
            $body = (string) $body;
            $parsedBody = json_decode($body);
        }

        return $parsedBody ?? null;
    }

    private static function getGlobalUploadedFiles()
    {
        switch (true) {
            case ($_FILES):
                foreach ($_FILES as $file => $metadata) {
                    // If the multiple files were uploaded for a field, leave it to the user to handle this
                    if (is_array($metadata['name'])) {
                        break;
                    }
                    $stream = $metadata['tmp_name'];
                    $error = $metadata['error'];
                    $size = $metadata['size'];
                    $name = $metadata['name'];
                    $type = $metadata['type'];

                    /**
                     * If no file is selected for upload for a field,
                     * an empty stream is created to simulate an uploaded
                     * file for that field convenience purposes
                     */
                    if (!$stream && !$size) {
                        $stream = tmpfile();
                    }
                    $uploadedFiles[$file] = new UploadedFile($stream, $size, $error, $name, $type);
                }
                break;

            case (preg_match("/PUT/i", $_SERVER['REQUEST_METHOD'])):
                // Transfer the content of the uploaded file into a temporary file
                $stream = new Stream(tmpfile());
                $body = new Stream("php://input");
                while ($data = $body->read(1024)) {
                    $stream->write($data);
                }

                $type = mime_content_type($stream);
                $uploadedFiles['upload_put'] = new UploadedFile($stream, clientMediaType: $type);
                break;
        }

        return $uploadedFiles ?? [];
    }

    private static function getGlobalQueryParams()
    {
        if ($query = $_SERVER['QUERY_STRING']) {
            if ($query[0] == '?') {
                $query = substr($query, 1);
            }
            foreach (explode('&', $query) as $pair) {
                $pair_elements = explode('=', $pair);
                if (count($pair_elements) == 2) {
                    $params[$pair_elements[0]] = $pair_elements[1];
                } else {
                    $params[$pair_elements[0]] = null;
                }
            }
        }

        return $params ?? [];
    }

    /**
     * @return string[][]
     **/
    private static function altApacheRequestHeaders()
    {
        foreach ($_SERVER as $key => $value) {
            if (stripos($key, 'HTTP_') !== false) {
                $headerKey = str_ireplace('HTTP_', '', $key);
                $headers[self::explodeHeader($headerKey)] = $value;
            } else if (stripos($key, 'CONTENT_') !== false) {
                $headers[self::explodeHeader($key)] = $value;
            }
        }

        return $headers ?? [];
    }

    private static function explodeHeader($header)
    {
        $headerParts = explode('_', $header);
        $headerKey = ucwords(strtolower(implode(' ', $headerParts)));
        return str_replace(' ', '-', $headerKey);
    }
}
