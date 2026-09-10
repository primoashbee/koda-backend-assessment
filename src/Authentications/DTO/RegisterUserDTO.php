<?php

declare(strict_types=1);

namespace Domain\Authentications\DTO;

class RegisterUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
