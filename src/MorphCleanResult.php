<?php

declare(strict_types=1);

namespace Cesargb\ModelToolkit;

final readonly class MorphCleanResult
{
    private function __construct(
        private int $deletedCount,
        private ?string $error,
    ) {}

    public static function success(int $deletedCount): self
    {
        return new self($deletedCount, null);
    }

    public static function failure(string $error): self
    {
        return new self(0, $error);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }

    public function failed(): bool
    {
        return $this->error !== null;
    }

    public function deletedCount(): int
    {
        return $this->deletedCount;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}
