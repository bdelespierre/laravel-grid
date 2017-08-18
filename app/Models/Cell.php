<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cell extends Model
{
    use Concerns\HasVersion,
        Concerns\HasUuid;

    protected $fillable = ['x', 'y', 'data'];

    protected $casts = ['data' => 'array'];

    protected $autocommit = true;

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function has($key)
    {
        return array_has($this->data, $key);
    }

    public function get($key, $default = null)
    {
        return array_get($this->data, $key, $default);
    }

    public function set($key, $value)
    {
        $data = $this->data ?: [];
        $this->data = array_set($data, $key, $value);

        if ($this->autocommit) {
            $this->save();
        }
    }

    public function add($key, $value)
    {
        $this->data = array_add($this->data ?: [], $key, $value);

        if ($this->autocommit) {
            $this->save();
        }
    }

    public function pull($key, $default = null)
    {
        $data = $this->data ?: [];
        $value = array_pull($data, $key, $default);

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

    public function getAdjacents(): array
    {
        $adjacents = [];
        $map = $this->map;

        for ($i = -1; $i < 2; $i++) {
            for ($j = -1; $j < 2; $j++) {
                $x = $this->x + $i;
                $y = $this->y + $j;

                // exclude $this
                if ($i == 0 && $j == 0) {
                    continue;
                }

                // stay within the map constraints
                if ($x < 0 || $x > $map->width -1 || $y < 0 || $y > $map->height -1) {
                    continue;
                }

                $adjacents[] = $map->at($x, $y);
            }
        }

        return $adjacents;
    }

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
