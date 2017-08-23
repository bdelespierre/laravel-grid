<?php

namespace App\Models\Concerns;

trait HasData
{
    protected $dataAttribute = "data";

    public $autocommit = true;

    /**
     * ------------------------------------------------------------------------
     * Data attribute IO
     * ------------------------------------------------------------------------
     */

    public function has($key)
    {
        return array_has($this[$this->dataAttribute], $key);
    }

    public function get($key, $default = null)
    {
        return array_get($this[$this->dataAttribute], $key, $default);
    }

    public function set($key, $value)
    {
        $data = $this[$this->dataAttribute] ?: [];
        array_set($data, $key, $value);
        $this[$this->dataAttribute] = $data;

        if ($this->autocommit) {
            $this->save();
        }
    }

    public function add($key, $value)
    {
        $this[$this->dataAttribute] = array_add($this[$this->dataAttribute] ?: [], $key, $value);

        if ($this->autocommit) {
            $this->save();
        }
    }

    public function pull($key, $default = null)
    {
        $data = $this[$this->dataAttribute] ?: [];
        $value = array_pull($data, $key, $default);
        $this[$this->dataAttribute] = $data;

        if ($this->autocommit) {
            $this->save();
        }

        return $value;
    }

    public function multi(callable $fn)
    {
        try {
            $this->autocommit = false;
            $fn($this);
            $this->save();
        } finally {
            $this->autocommit = true;
        }
    }

    /**
     * ------------------------------------------------------------------------
     * ArrayAccess methods
     * ------------------------------------------------------------------------
     */

    public function offsetExists($offset)
    {
        if (in_array($offset, $this->fillable)) {
            return parent::offsetExists($offset);
        }

        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        if (in_array($offset, $this->fillable)) {
            return parent::offsetGet($offset);
        }

        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        if (in_array($offset, $this->fillable)) {
            return parent::offsetSet($offset, $value);
        }

        return $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        if (in_array($offset, $this->fillable)) {
            return parent::offsetUnset($offset);
        }

        $this->pull($offset);
    }
}