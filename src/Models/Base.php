<?php

namespace Sinclair\Cosmos\Models;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Document
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @package Sinclair\Cosmos
 */
abstract class Base implements Arrayable
{
    /**
     * array @var
     */
    protected $attributes;

    /**
     * array @var
     */
    protected $original;

    /**
     * @var bool
     */
    protected $exists = false;

    /**
     * Document constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->original   = $attributes;
        $this->attributes = $attributes;
        $this->exists     = array_has($attributes, 'id');
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        if (array_has($this->attributes, $name)) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * @param null|array|mixed $id
     *
     * @param array            $attributes
     *
     * @return Base
     * @throws \Sinclair\Cosmos\CosmosException
     */
    public function create($id = null, $attributes = [])
    {
        if (is_array($id)) {
            $this->setAttributes($id);
        } else {
            $this->id = $id;
        }

        $attributes = $this->setAttributes($attributes)->newQuery()->create($this->attributes, $this->id);

        $this->setAttributes($attributes);

        $this->exists   = true;
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return Base
     * @throws \Sinclair\Cosmos\CosmosException
     */
    public function update($attributes = [])
    {
        $attributes = $this->setAttributes($attributes)->newQuery()->update($this->attributes, $this->id);

        $this->setAttributes($attributes);

        $this->original = $this->attributes;

        return $this;
    }

    /**
     * @return Base
     * @throws \Sinclair\Cosmos\CosmosException
     */
    public function delete()
    {
        if ($this->newQuery()->delete($this->id)) {
            $this->exists = false;
        }

        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return Base
     * @throws \Sinclair\Cosmos\CosmosException
     */
    public function save($attributes = [])
    {
        return $this->exists ? $this->update($attributes) : $this->create($this->id, $attributes);
    }

    /**
     * @return \Sinclair\Cosmos\Resources\Base
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * @return \Sinclair\Cosmos\Resources\Base
     */
    abstract public function newQuery();

    /**
     * @param array $attributes
     *
     * @return Base
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * @return mixed
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }
}
