<?php

namespace App\Services;

use Exception;

class MlException extends Exception
{
    public function __construct(
        public readonly string $errorCode = 'ML_ERROR',
        string $message = 'Prediksi gagal diproses. Coba lagi.',
        int $status = 503,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
