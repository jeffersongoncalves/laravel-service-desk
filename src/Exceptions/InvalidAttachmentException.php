<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use RuntimeException;

class InvalidAttachmentException extends RuntimeException
{
    public static function disallowedExtension(string $extension): self
    {
        return new self("The file extension [{$extension}] is not allowed.");
    }

    public static function fileTooLarge(int $sizeInKb, int $maxSizeInKb): self
    {
        return new self("The file size [{$sizeInKb} KB] exceeds the maximum allowed size of [{$maxSizeInKb} KB].");
    }
}
