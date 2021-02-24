<?php

namespace Sinclair\Cosmos\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sinclair\Cosmos\Document
 */
class Document extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'document';
    }
}
