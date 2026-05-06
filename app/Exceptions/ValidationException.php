<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    /**
     * Mảng chứa các lỗi validation.
     * @var array
     */
    public $errors = [];

    /**
     * Mảng chứa dữ liệu cũ đã nhập.
     * @var array
     */
    public $old = [];

    public function __construct(array $errors, array $old = [], $message = "Dữ liệu không hợp lệ.", $code = 422, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->old = $old;
    }
}
