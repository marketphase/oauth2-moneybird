<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Override;

class MoneybirdResourceOwner implements ResourceOwnerInterface
{
    /**
     * @param array<int, array<string, mixed>> $administrations
     */
    public function __construct(
        private array $administrations,
    ) {
    }

    #[Override]
    public function getId(): ?string
    {
        return isset($this->administrations[0]['id'])
            ? (string) $this->administrations[0]['id']
            : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Override]
    public function toArray(): array
    {
        return $this->administrations;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdministrations(): array
    {
        return $this->administrations;
    }
}
