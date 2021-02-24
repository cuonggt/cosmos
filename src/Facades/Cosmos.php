<?php

namespace Sinclair\Cosmos\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sinclair\Cosmos\Cosmos
 */
class Cosmos extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cosmos';
    }
}
