<?php

namespace Sinclair\Cosmos;

use GuzzleHttp\Client;

/**
 * Class Cosmos
 *
 * @package Sinclair\Cosmos
 */
class Cosmos
{
    public $documents;

    /**
     * Cosmos constructor.
     *
     * @param Client $client
     * @param array  $headers
     * @param null   $account
     * @param null   $db
     * @param null   $collection
     */
    public function __construct(Client $client, $headers = [], $account = null, $db = null, $collection = null)
    {
        $this->documents = new Resources\Document($client, $headers, $account, $db, $collection);
    }
}
