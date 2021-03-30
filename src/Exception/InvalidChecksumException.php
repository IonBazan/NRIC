<?php
declare(strict_types=1);

namespace IonBazan\NRIC\Exception;

use InvalidArgumentException;

class InvalidChecksumException extends InvalidArgumentException
{
    public function __construct(string $checksum)
    {
        parent::__construct('Invalid checksum: ' . $checksum);
    }
}
