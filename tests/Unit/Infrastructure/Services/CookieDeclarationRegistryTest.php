<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Unit\Infrastructure\Services;

use Modules\CookieConsent\Application\DTOs\DeclaredCookieDTO;
use Modules\CookieConsent\Domain\Enums\CookieType;
use Modules\CookieConsent\Infrastructure\Services\CookieDeclarationRegistry;
use PHPUnit\Framework\TestCase;

final class CookieDeclarationRegistryTest extends TestCase
{
    public function test_declare_keeps_one_entry_per_name(): void
    {
        $registry = new CookieDeclarationRegistry;

        $registry->declare(new DeclaredCookieDTO('gt_guest_registrations', 'preferences', 'GuildForge', 'First purpose'));
        $registry->declare(new DeclaredCookieDTO('gt_guest_registrations', 'preferences', 'GuildForge', 'Second purpose'));

        $all = $registry->all();

        $this->assertCount(1, $all);
        $this->assertSame('Second purpose', $all[0]->purpose);
    }

    public function test_all_returns_declarations_in_order(): void
    {
        $registry = new CookieDeclarationRegistry;

        $registry->declare(new DeclaredCookieDTO('first', 'necessary', 'GuildForge', 'One'));
        $registry->declare(new DeclaredCookieDTO('second', 'preferences', 'GuildForge', 'Two', '1 year'));

        $all = $registry->all();

        $this->assertSame(['first', 'second'], array_map(fn (DeclaredCookieDTO $c) => $c->name, $all));
        $this->assertSame(CookieType::FirstParty, $all[0]->type);
        $this->assertSame('1 year', $all[1]->duration);
    }
}
