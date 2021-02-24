<?php

namespace Sinclair\Cosmos\Models;

/**
 * Class Document
 *
 * @property int id
 * @package Sinclair\Cosmos
 */
class Document extends Base
{
    /**
     * @return \Sinclair\Cosmos\Resources\Document
     */
    public function newQuery()
    {
        return app('cosmos')->documents;
    }
}
