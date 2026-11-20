<?php
declare(strict_types=1);

namespace RestaurantChain\Models;

interface FileConvertible
{
    public function toString(): string;
    public function toHTML(): string;
    public function toMarkdown(): string;
    public function toArray(): array;
}
