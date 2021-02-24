<?php

namespace Sinclair\Cosmos\Resources;

use GuzzleHttp\Client;
use Illuminate\Database\Query\Builder;
use Sinclair\Cosmos\CosmosException;
use Sinclair\Cosmos\Factory;

/**
 * Class Cosmos
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @package Sinclair\Cosmos
 */
abstract class Base
{
    /**
     * The API version of the Azure Cosmos REST API
     */
    const API_VERSION = '2017-02-22';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    protected $builder;

    /**
     * The name of the database to query against
     *
     * @var string
     */
    protected $db;

    /**
     * @var Client
     */
    protected $http;

    /**
     * @var
     */
    protected $url;

    /**
     * @var
     */
    protected $collection;

    /**
     * @var
     */
    protected $account;

    /**
     * @var Document
     */
    public $documents;

    /**
     * @var
     */
    protected $model = '';

    /**
     * @var string
     */
    protected $resultKey;

    /**
     * Base constructor.
     *
     * @param Client $client
     * @param array  $headers
     * @param null   $account
     * @param null   $db
     * @param null   $collection
     */
    public function __construct(Client $client, $headers = [], $account = null, $db = null, $collection = null)
    {
        $this->account    = $account ?? config('cosmos.account_name');
        $this->db         = $db ?? config('cosmos.defaults.database.name');
        $this->collection = $collection ?? config('cosmos.defaults.database.collection');
        $this->createUrl();

        $this->setHeaders($headers);

        $this->builder = app(Builder::class);

        $this->http = $client;

        $this->resultKey = str_plural(class_basename($this));
    }

    /**
     * @param $name
     * @param $args
     *
     * @return Base
     */
    public function __call($name, $args)
    {
        if (method_exists($this->builder, $name)) {
            $this->builder = $this->builder->{$name}(...$args);

            return $this;
        }
    }

    /**
     * @param mixed $account
     *
     * @return Base
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this->createUrl();
    }

    /**
     * @param string $db
     *
     * @return Base
     */
    public function setDatabase(string $db)
    {
        $this->db = $db;

        return $this->createUrl();
    }

    /**
     * @param mixed $collection
     *
     * @return Base
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this->createUrl();
    }

    /**
     * @return Base
     */
    protected function createUrl()
    {
        $db         = $this->db;
        $account    = $this->account;
        $collection = $this->collection;
        $this->url  = implode('', ['https://', $account, '.documents.azure.com/dbs/', $db, '/colls/', $collection]);

        return $this;
    }

    /**
     * @param null $limit
     *
     * @return \Illuminate\Support\Collection
     * @throws CosmosException
     */
    abstract public function all($limit = null);

    /**
     * @param null $limit
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    abstract public function allRaw($limit = null);

    /**
     * @param null $limit
     *
     * @return \Illuminate\Support\Collection
     * @throws CosmosException
     */
    abstract public function get($limit = null);

    /**
     * @param null $limit
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    abstract public function getRaw($limit = null);

    /**
     * @param      $data
     * @param null $id
     *
     * @return array
     * @throws CosmosException
     */
    abstract public function create($data, $id = null);

    /**
     * @param      $data
     * @param null $id
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    abstract public function createRaw($data, $id = null);

    /**
     * @param      $data
     * @param null $id
     *
     * @return array
     * @throws CosmosException
     */
    abstract public function update($data, $id);

    /**
     * @param      $data
     * @param null $id
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    abstract public function updateRaw($data, $id);

    /**
     * @param null $id
     *
     * @return bool
     * @throws CosmosException
     */
    abstract public function delete($id);

    /**
     * @param null $id
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    abstract public function deleteRaw($id);

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param $headers
     *
     * @return Base
     */
    protected function setHeaders($headers)
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['x-ms-date']    = now()->toRfc7231String();
        $this->headers['x-ms-version'] = self::API_VERSION;
        $this->headers['Accept']       = 'application/json';
        $this->headers                 = array_replace($this->headers, $headers);

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return \Illuminate\Support\Collection
     * @throws CosmosException
     */
    public function first($columns = ['*'])
    {
        $this->builder = $this->builder->take(1);
        if ($columns != ['*']) {
            $this->builder = $this->builder->select($columns);
        }

        return $this->get(1)->first();
    }

    /**
     * @param $response
     *
     * @return Factory
     */
    protected function format(\Psr\Http\Message\ResponseInterface $response): Factory
    {
        return (new Factory($response->getBody()->getContents(), $this->model, $this->resultKey));
    }
}
