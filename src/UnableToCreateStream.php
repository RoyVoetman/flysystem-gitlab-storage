<?php

declare(strict_types=1);

namespace RoyVoetman\FlysystemGitlab;

use League\Flysystem\FilesystemOperationFailed;
use RuntimeException;

/**
 * Class UnableToCreateStream
 *
 * @package RoyVoetman\FlysystemGitlab
 */
final class UnableToCreateStream extends RuntimeException implements FilesystemOperationFailed
{
    /**
     * @return string
     */
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_READ;
    }
}
