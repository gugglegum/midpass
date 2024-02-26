<?php

declare(strict_types = 1);

namespace App;

class ResourceManager
{
    private \Luracast\Config\Config $config;

    public function getConfig(): \Luracast\Config\Config
    {
        if (!isset($this->config)) {
            $dotenv = new \Dotenv\Dotenv(PROJECT_ROOT_DIR . '/..');
            $dotenv->overload();
            $dotenv->required(['EMAIL', 'PASSWORD', 'TIMEZONE', 'COUNTRY_ID', 'SERVICE_PROVIDER_ID'])->notEmpty();
            $this->config = \Luracast\Config\Config::init(PROJECT_ROOT_DIR . '/config');
        }
        return $this->config;
    }

}
