<?php

namespace App\Services\Inventory;

use RuntimeException;

class InventoryReconciliationException extends RuntimeException
{
    /** @param list<string> $errors */
    public function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
