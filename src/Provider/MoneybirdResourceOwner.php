<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class MoneybirdResourceOwner implements ResourceOwnerInterface
{
    /**
     * @var array<string, mixed>
     */
    private $administration;

    /**
     * @param array<string, mixed> $administration
     */
    public function __construct(array $administration)
    {
        $this->administration = $administration;
    }

    public function getId()
    {
        return (string) $this->administration['id'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return (string) $this->administration['name'];
    }

    public function toArray()
    {
        return $this->administration;
    }
}
