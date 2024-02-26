<?php

declare(strict_types=1);
namespace App\Console\Commands;

use App\ResourceManager;

abstract class AbstractCommand
{
    protected ResourceManager $resourceManager;

    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
        date_default_timezone_set($this->resourceManager->getConfig()->get('app.timezone'));
    }

    abstract public function __invoke(): ?int;
}
