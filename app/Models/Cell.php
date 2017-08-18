<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Cell extends Model
{
    use Concerns\HasVersion,
        Concerns\HasUuid;

    protected $fillable = ['x', 'y', 'data'];

    protected $casts = ['data' => 'array'];

    protected $autocommit = true;

    public function getCoordinatesAttribute()
    {
        return [$this->x, $this->y];
    }

    public function getAdjacentsAttribute()
    {
        return $this->getAdjacents();
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    /**
     * ------------------------------------------------------------------------
     * Data IO
     * ------------------------------------------------------------------------
     */

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

    /**
     * ------------------------------------------------------------------------
     * Adjacent cells
     * ------------------------------------------------------------------------
     */

    public function getAdjacents(): Collection
    {
        $set = new Collection;
        foreach (['n','ne','e','se','s','sw','w','nw'] as $dir) {
            $set[] = $this->getAdjacent($dir);
        }

        return $set;
    }

    public function getAdjacent($direction): self
    {
        $dir = strtolower(str_replace([' ', '-', ':'], '', $direction));

        if (in_array($this->map->type, [Map::TYPE_OVERHEAD, Map::TYPE_ANGLED_ISOMETRIC])) {
            return static::getAdjacentForOverhead($dir, $this->map, $this->x, $this->y);
        }

        if ($this->map->type == Map::TYPE_LAYERED_ISOMETRIC) {
            return static::getAdjacentForLayeredIsometric($dir, $this->map, $this->x, $this->y);
        }
    }

    protected static function getAdjacentForOverhead($dir, $map, $x, $y): self
    {
        switch ($dir) {
            case 'n'  : case 'north'     : list($x , $y) = [$x   , $y-1];
            case 'ne' : case 'northeast' : list($x , $y) = [$x+1 , $y-1];
            case 'e'  : case 'east'      : list($x , $y) = [$x+1 , $y  ];
            case 'se' : case 'southeast' : list($x , $y) = [$x+1 , $y+1];
            case 's'  : case 'south'     : list($x , $y) = [$x   , $y+1];
            case 'sw' : case 'southwest' : list($x , $y) = [$x-1 , $y+1];
            case 'w'  : case 'west'      : list($x , $y) = [$x-1 , $y  ];
            case 'nw' : case 'northwest' : list($x , $y) = [$x-1 , $y-1];
        }

        return $map->at($x, $y);
    }

    protected static function getAdjacentForLayeredIsometric($dir, $map, $x, $y): self
    {
        $o = $y % 2 == 0 ? 1 : 0;

        switch ($dir) {
            case 'n'  : case 'north'     : list($x , $y) = [$x   , $y-2];
            case 'ne' : case 'northeast' : list($x , $y) = [$x   , $y-1];
            case 'e'  : case 'east'      : list($x , $y) = [$x+1 , $y  ];
            case 'se' : case 'southeast' : list($x , $y) = [$x   , $y+1];
            case 's'  : case 'south'     : list($x , $y) = [$x   , $y+2];
            case 'sw' : case 'southwest' : list($x , $y) = [$x-1 , $y+1];
            case 'w'  : case 'west'      : list($x , $y) = [$x-1 , $y  ];
            case 'nw' : case 'northwest' : list($x , $y) = [$x-1 , $y-1];
        }

        return $map->at($x + $o, $y);
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
