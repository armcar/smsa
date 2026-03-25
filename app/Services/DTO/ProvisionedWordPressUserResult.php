<?php

namespace App\Services\DTO;

final readonly class ProvisionedWordPressUserResult
{
    public function __construct(
        public bool $created,
        public string $username,
        public ?string $plainPassword,
        public int $wpUserId,
        public string $message,
    ) {
    }
}

