<?php

namespace OffbeatWP\Eloquent;

use OffbeatWP\Services\AbstractService;

class Service extends AbstractService
{
    public $bindings = [
        'db' => EloquentManager::class
    ];

    public function register()
    {
    }
}
