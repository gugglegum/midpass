<?php

declare(strict_types = 1);

namespace App;

class ResourceManager
{
    private \Luracast\Config\Config $config;

    public function getConfig(): \Luracast\Config\Config
    {
        if (!isset($this->config)) {
            $dotenv = \Dotenv\Dotenv::createImmutable(PROJECT_ROOT_DIR . '/..', 'config.txt');
            $dotenv->load();
            $dotenv->required(['EMAIL', 'PASSWORD', 'TIMEZONE', 'COUNTRY_ID', 'SERVICE_PROVIDER_ID'])->notEmpty();
            $this->config = \Luracast\Config\Config::init(PROJECT_ROOT_DIR . '/config');
        }
        return $this->config;
    }

}
