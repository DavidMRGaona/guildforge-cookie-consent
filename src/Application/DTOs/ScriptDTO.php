<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

use Modules\CookieConsent\Domain\Entities\CookieScript;

final readonly class ScriptDTO
{
    public function __construct(
        public string $id,
        public string $categoryId,
        public string $name,
        public ?string $scriptHead,
        public ?string $scriptBodyStart,
        public ?string $scriptBodyEnd,
        public ?string $noscriptContent,
        public int $sortOrder,
    ) {}

    public static function fromEntity(CookieScript $script): self
    {
        return new self(
            id: $script->id()->value(),
            categoryId: $script->categoryId()->value(),
            name: $script->name(),
            scriptHead: $script->scriptHead(),
            scriptBodyStart: $script->scriptBodyStart(),
            scriptBodyEnd: $script->scriptBodyEnd(),
            noscriptContent: $script->noscriptContent(),
            sortOrder: $script->sortOrder(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'categoryId' => $this->categoryId,
            'name' => $this->name,
            'scriptHead' => $this->scriptHead,
            'scriptBodyStart' => $this->scriptBodyStart,
            'scriptBodyEnd' => $this->scriptBodyEnd,
            'noscriptContent' => $this->noscriptContent,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
