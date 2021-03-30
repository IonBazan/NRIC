<?php
declare(strict_types=1);

namespace IonBazan\NRIC\Exception;

use InvalidArgumentException;

class InvalidFormatException extends InvalidArgumentException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('ID "%s" has invalid format', $id));
    }
}
