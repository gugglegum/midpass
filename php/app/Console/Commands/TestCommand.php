<?php

declare(strict_types=1);
namespace App\Console\Commands;

class TestCommand extends AbstractCommand
{
    public function __invoke(): ?int
    {
        echo "hello\n";
        return 0;
    }
}
