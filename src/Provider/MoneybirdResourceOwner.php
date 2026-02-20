<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Override;

class MoneybirdResourceOwner implements ResourceOwnerInterface
{
    /**
     * @param array<string, mixed> $administration
     */
    public function __construct(
        private array $administration,
    ) {
    }

    #[Override]
    public function getId(): string
    {
        return (string) $this->administration['id'];
    }

    public function getName(): string
    {
        return (string) $this->administration['name'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        return $this->administration;
    }
}