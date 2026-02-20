<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Test\Provider;

use PHPUnit\Framework\TestCase;
use Staxxer\OAuth2\Client\Provider\MoneybirdResourceOwner;

class MoneybirdResourceOwnerTest extends TestCase
{
    public function testGetIdReturnsFirstAdministrationId(): void
    {
        $owner = new MoneybirdResourceOwner([
            ['id' => 123456789, 'name' => 'Test Administration'],
            ['id' => 987654321, 'name' => 'Other Administration'],
        ]);

        self::assertSame('123456789', $owner->getId());
    }

    public function testGetIdReturnsNullWhenEmpty(): void
    {
        $owner = new MoneybirdResourceOwner([]);

        self::assertNull($owner->getId());
    }

    public function testGetIdReturnsNullWhenNoIdInFirstEntry(): void
    {
        $owner = new MoneybirdResourceOwner([
            ['name' => 'Test Administration'],
        ]);

        self::assertNull($owner->getId());
    }

    public function testGetAdministrations(): void
    {
        $administrations = [
            ['id' => 123456789, 'name' => 'Test Administration'],
            ['id' => 987654321, 'name' => 'Other Administration'],
        ];

        $owner = new MoneybirdResourceOwner($administrations);

        self::assertSame($administrations, $owner->getAdministrations());
    }

    public function testToArrayReturnsAdministrations(): void
    {
        $administrations = [
            ['id' => 123456789, 'name' => 'Test Administration'],
        ];

        $owner = new MoneybirdResourceOwner($administrations);

        self::assertSame($administrations, $owner->toArray());
    }
}
