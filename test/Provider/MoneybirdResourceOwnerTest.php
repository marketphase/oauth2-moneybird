<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Test\Provider;

use PHPUnit\Framework\TestCase;
use Staxxer\OAuth2\Client\Provider\MoneybirdResourceOwner;

class MoneybirdResourceOwnerTest extends TestCase
{
    public function testGetId(): void
    {
        $owner = new MoneybirdResourceOwner([
            'id' => 123456789,
            'name' => 'Test Administration',
        ]);

        self::assertSame('123456789', $owner->getId());
    }

    public function testGetName(): void
    {
        $owner = new MoneybirdResourceOwner([
            'id' => 123456789,
            'name' => 'Test Administration',
        ]);

        self::assertSame('Test Administration', $owner->getName());
    }

    public function testToArray(): void
    {
        $administration = [
            'id' => 123456789,
            'name' => 'Test Administration',
            'language' => 'nl',
            'currency' => 'EUR',
            'country' => 'NL',
        ];

        $owner = new MoneybirdResourceOwner($administration);

        self::assertSame($administration, $owner->toArray());
    }
}