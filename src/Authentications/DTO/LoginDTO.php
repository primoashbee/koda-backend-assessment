<?php

declare(strict_types=1);

namespace Domain\Authentications\DTO;

class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
