<?php

declare(strict_types=1);

namespace Tym\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class UploadedFile implements UploadedFileInterface
{
    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    /**
     * The stream representation of the file.
     */
    private StreamInterface $stream;

    /**
     * The upload error code.
     */
    private int $error;

    /**
     * The filesize in bytes.
     */
    private ?int $size;

    /**
     * The file's original name.
     */
    private ?string $clientFilename;

    /**
     * The file's MIME type.
     */
    private ?string $clientMediaType;

    private bool $moved = false;

    /**
     * Initializes the uploaded file.
     * 
     * @param StreamInterface|resource|string $stream The uploaded file.
     * @param int $size The file's size in bytes.
     * @param int $error The upload error code, it must be one of PHP's UPLOAD_ERR_XXX constants.
     * @param string $clientFilename The file's client name.
     * @param string $clientMediaType The file's media type.
     * 
     * @throws \InvalidArgumentException If an invalid stream or error code is passed
     **/
    public function __construct(
        $stream,
        int $size = null,
        int $error = UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ) {
        if (!($stream instanceof StreamInterface) && !is_resource($stream) && !is_string($stream)) {
            throw new \InvalidArgumentException(sprintf("A resource, an instance of %s or the path to a file must be passed.", StreamInterface::class));
        }
        if (is_resource($stream) || is_string($stream)) {
            $stream = new Stream($stream);
        }
        if (!in_array($error, self::UPLOAD_ERRORS, true)) {
            throw new \InvalidArgumentException("Invalid error status.");
        }

        $this->stream = $stream;
        $this->error = $error;
        $this->size = $size ?? $this->stream->getSize();
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException('The file has already been moved.');
        }
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("No file is available due to an error during upload.");
        }

        return $this->stream;
    }

    public function moveTo($targetPath)
    {
        if (!is_string($targetPath)) {
            throw new \InvalidArgumentException("The path must be a string.");
        }
        if (is_dir($targetPath)) {
            throw new \InvalidArgumentException("[$targetPath] points to a directory.");
        }
        if ($this->moved) {
            throw new \RuntimeException('The file has already been moved.');
        }
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("No stream is available due to an upload error");
        }
        if (is_null($original_name = $this->stream->getMetadata('uri'))) {
            throw new \RuntimeException("Stream was detached");
        }

        // If the file is a PHP I/O stream
        if (preg_match('/php:\/{2}\w+/', $original_name)) {
            $temp_name = sys_get_temp_dir() . '/' . random_int(1000000, 1000000000) . '.tmp';
            $temp_file = fopen($temp_name, 'w');
            $contents = (string) $this->stream;
            fwrite($temp_file, $contents);
            $original_name = $temp_name;
            fclose($temp_file);
        }

        // Move the file to the given location
        if (!($this->moved = rename($original_name, $targetPath))) {
            throw new \RuntimeException("The file could not be moved to [$targetPath]");
        }
        $this->error = UPLOAD_ERR_OK;

        return true;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}
