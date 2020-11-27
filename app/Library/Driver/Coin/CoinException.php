<?php

declare(strict_types=1);

namespace Driver\Coin;

use App\Constants\ErrorCode;
use Exception;

class CoinException extends Exception
{
    public function __construct(int $code = 0, string $message = null, $replace = null)
    {
        if (is_null($message)) {
            $message = ErrorCode::getMessage($code, $replace);
        }

        parent::__construct($message, $code);
    }
}
