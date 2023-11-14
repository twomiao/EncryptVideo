<?php

declare(strict_types=1);

namespace Compiler\encrypt;

final class Chunk
{
    public function __construct(public int $length, public bool $isEncrypted)
    {

    }
}
