<?php

declare(strict_types=1);

namespace Compiler\Encrypt;

class Info
{
    public function __construct(
        public string $filename,
        public int $filesize,
        public string $hash,
        public array $pointers
    ) {}
}
