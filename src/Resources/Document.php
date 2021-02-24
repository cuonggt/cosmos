<?php

namespace Sinclair\Cosmos\Resources;

use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;
use Sinclair\Cosmos\CosmosException;

/**
 * Class Document
 *
 * @package Sinclair\Cosmos\Resources
 */
class Document extends Base
{
    /**
     * @var string
     */
    protected $model = \Sinclair\Cosmos\Models\Document::class;

    /**
     * @param $response
     *
     * @throws \Exception
     */
    protected function handleSessionToken($response): void
    {
        if ($response->hasHeader('x-ms-session-token')) {
            cache()->forever('x-ms-session-token', $response->getHeader('x-ms-session-token'));
        }
    }

    /**
     * @throws \Exception
     */
    protected function setSessionToken()
    {
        $sessionToken = cache('x-ms-session-token');
        if ($sessionToken) {
            $this->setHeader('x-ms-session-token', $sessionToken);
        }
    }

    /**
     * @return Document
     */
    protected function createUrl()
    {
        parent::createUrl();

        $this->url .= '/docs';

        return $this;
    }

    /**
     * @param null $limit
     *
     * @return \Illuminate\Support\Collection
     * @throws CosmosException
     */
    public function all($limit = null)
    {
        return $this->format($this->allRaw($limit))->toCollection();
    }

    /**
     * @param $limit
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    public function allRaw($limit = null): \Psr\Http\Message\ResponseInterface
    {
        $this->setSessionToken();

        if (is_int($limit)) {
            $this->headers['x-ms-max-item-count'] = $limit;
        }

        $uri = 'dbs/' . $this->db . '/colls/' . $this->collection;

        $this->headers['Authorization'] = (new KeyGenerator())->make('get', 'docs', $uri, $this->headers['x-ms-date']);

        try {
            $response = $this->http->get($this->url, ['headers' => $this->headers]);

            $this->handleSessionToken($response);

            if ($response->getStatusCode() === 200) {
                return $response;
            }
            throw new CosmosException($response->getBody()->getContents(), $response->getStatusCode());
        } catch (ClientException $e) {

            throw new CosmosException($e->getResponse()->getBody()->getContents(), $e->getCode(), $e);
        }
    }

    /**
     * @param null $limit
     *
     * @return \Illuminate\Support\Collection
     * @throws CosmosException
     */
    public function get($limit = null)
    {
        return $this->format($this->getRaw($limit))->toCollection();
    }

    /**
     * @param $limit
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    public function getRaw($limit = null): \Psr\Http\Message\ResponseInterface
    {
        $this->setSessionToken();

        if (is_int($limit)) {
            $this->headers['x-ms-max-item-count'] = $limit;
        }

        $this->headers['Content-Type']            = 'application/query+json';
        $this->headers['x-ms-documentdb-isquery'] = true;

        $uri = 'dbs/' . $this->db . '/colls/' . $this->collection;

        $this->headers['Authorization'] = (new KeyGenerator())->make('post', 'docs', $uri, $this->headers['x-ms-date']);

        $sql = $this->sql();

        $this->headers['Content-Length'] = strlen($sql);

        try {
            $response = $this->http->post($this->url, [
                'json'    => [
                    'query'      => $sql,
                    'parameters' => [],
                ],
                'headers' => $this->headers,
            ]);

            $this->handleSessionToken($response);

            if ($response->getStatusCode() === 200) {
                return $response;
            }
            throw new CosmosException($response->getBody(), $response->getStatusCode());
        } catch (ClientException $e) {

            throw new CosmosException($e->getResponse()->getBody()->getContents(), $e->getCode(), $e);
        }
    }

    /**
     * @param      $data
     * @param null $id
     *
     * @return array
     * @throws CosmosException
     */
    public function create($data, $id = null)
    {
        return $this->format($this->createRaw($data, $id))->toArray();
    }

    /**
     * @param $data
     * @param $id
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    public function createRaw($data, $id = null): \Psr\Http\Message\ResponseInterface
    {
        try {
            $this->setSessionToken();

            $uri = 'dbs/' . $this->db . '/colls/' . $this->collection;

            $this->headers['Authorization'] = (new KeyGenerator())->make('post', 'docs', $uri, $this->headers['x-ms-date']);

            $data['id'] = $id ?? array_get($data, 'id', Uuid::uuid4()->toString());

            $response = $this->http->post($this->url, [
                'json'    => $data,
                'headers' => $this->headers,
            ]);

            $this->handleSessionToken($response);

            if ($response->getStatusCode() === 201) {
                return $response;
            }
            throw new CosmosException($response->getBody(), $response->getStatusCode());
        } catch (ClientException $e) {
            throw new CosmosException($e->getResponse()->getBody()->getContents(), $e->getCode(), $e);
        }
    }

    /**
     * @param      $data
     * @param null $id
     *
     * @return array
     * @throws CosmosException
     */
    public function update($data, $id)
    {
        return $this->format($this->updateRaw($data, $id))->toArray();
    }

    /**
     * @param $data
     * @param $id
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    public function updateRaw($data, $id): \Psr\Http\Message\ResponseInterface
    {
        try {
            $this->setSessionToken();

            $uri = 'dbs/' . $this->db . '/colls/' . $this->collection . '/docs/' . $id;

            $this->headers['Authorization'] = (new KeyGenerator())->make('put', 'docs', $uri, $this->headers['x-ms-date']);

            $response = $this->http->put($this->url . '/' . $id, [
                'json'    => compact('id') + $data,
                'headers' => $this->headers,
            ]);

            $this->handleSessionToken($response);

            if ($response->getStatusCode() === 200) {
                return $response;
            }
            throw new CosmosException($response->getBody(), $response->getStatusCode());
        } catch (ClientException $e) {
            throw new CosmosException($e->getResponse()->getBody()->getContents(), $e->getCode(), $e);
        }
    }

    /**
     * @param null $id
     *
     * @return bool
     * @throws CosmosException
     */
    public function delete($id)
    {
        return $this->deleteRaw($id)->getStatusCode() === 204;
    }

    /**
     * @param $id
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws CosmosException
     */
    public function deleteRaw($id): \Psr\Http\Message\ResponseInterface
    {
        try {
            $this->setSessionToken();

            $uri = 'dbs/' . $this->db . '/colls/' . $this->collection . '/docs/' . $id;

            $this->headers['Authorization'] = (new KeyGenerator())->make('delete', 'docs', $uri, $this->headers['x-ms-date']);

            $response = $this->http->delete($this->url . '/' . $id, ['headers' => $this->headers]);

            $this->handleSessionToken($response);

            return $response;
        } catch (ClientException $e) {
            throw new CosmosException($e->getResponse()->getBody()->getContents(), $e->getCode(), $e);
        }
    }

    /**
     * @return mixed|string
     */
    protected function sql()
    {
        $sql = str_replace('?', '"?"', $this->builder->toSql());

        return str_replace_array('?', $this->builder->getBindings(), $sql);
    }
}
