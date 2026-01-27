<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Entities;

use DateTimeImmutable;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Domain\ValueObjects\ScriptId;

final class CookieScript
{
    public function __construct(
        private readonly ScriptId $id,
        private readonly CategoryId $categoryId,
        private string $name,
        private ?string $description = null,
        private ?string $scriptHead = null,
        private ?string $scriptBodyStart = null,
        private ?string $scriptBodyEnd = null,
        private ?string $noscriptContent = null,
        private int $sortOrder = 0,
        private bool $isActive = true,
        private readonly ?DateTimeImmutable $createdAt = null,
        private readonly ?DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): ScriptId
    {
        return $this->id;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function scriptHead(): ?string
    {
        return $this->scriptHead;
    }

    public function scriptBodyStart(): ?string
    {
        return $this->scriptBodyStart;
    }

    public function scriptBodyEnd(): ?string
    {
        return $this->scriptBodyEnd;
    }

    public function noscriptContent(): ?string
    {
        return $this->noscriptContent;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasHeadScript(): bool
    {
        return $this->scriptHead !== null && $this->scriptHead !== '';
    }

    public function hasBodyStartScript(): bool
    {
        return $this->scriptBodyStart !== null && $this->scriptBodyStart !== '';
    }

    public function hasBodyEndScript(): bool
    {
        return $this->scriptBodyEnd !== null && $this->scriptBodyEnd !== '';
    }

    public function hasNoscriptContent(): bool
    {
        return $this->noscriptContent !== null && $this->noscriptContent !== '';
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function update(
        string $name,
        ?string $description = null,
        ?string $scriptHead = null,
        ?string $scriptBodyStart = null,
        ?string $scriptBodyEnd = null,
        ?string $noscriptContent = null,
        int $sortOrder = 0,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->scriptHead = $scriptHead;
        $this->scriptBodyStart = $scriptBodyStart;
        $this->scriptBodyEnd = $scriptBodyEnd;
        $this->noscriptContent = $noscriptContent;
        $this->sortOrder = $sortOrder;
    }
}
