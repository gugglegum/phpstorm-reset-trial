<?php

declare(strict_types=1);

namespace App\Exceptions;

class UserAbortException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Abort by user');
    }
}
