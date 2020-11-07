<?php

declare(strict_types=1);

namespace RoyVoetman\FlysystemGitlab;

use League\Flysystem\FilesystemOperationFailed;
use RuntimeException;

final class UnableToCreateStream extends RuntimeException implements FilesystemOperationFailed
{
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_READ;
    }
}
