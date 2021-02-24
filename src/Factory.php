<?php

namespace Sinclair\Cosmos;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Decorator
 *
 * @package Sinclair\Cosmos
 */
class Factory implements Arrayable
{
    /**
     * @var array
     */
    protected $contents;

    /**
     * @var string
     */
    private $model;

    /**
     * @var string
     */
    private $key;

    /**
     * Decorator constructor.
     *
     * @param        $contents
     * @param string $model
     * @param string $key
     */
    public function __construct($contents, $model = '', $key = '')
    {
        $this->contents = is_string($contents) ? json_decode($contents, true) : $contents;
        $this->model    = $model;
        $this->key      = $key;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->contents;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function toCollection()
    {
        return collect(array_get($this->toArray(), $this->key, []))->mapInto($this->model);
    }
}
