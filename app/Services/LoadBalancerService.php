<?php

namespace App\Services;

class LoadBalancerService
{
    protected static int $currentServerIndex = 0;

    protected array $servers = [
        'Server-1',
        'Server-2',
        'Server-3',
    ];

    public function getNextServer(): string
    {
        $server = $this->servers[self::$currentServerIndex];

        self::$currentServerIndex =
            (self::$currentServerIndex + 1) % count($this->servers);

        return $server;
    }
}
