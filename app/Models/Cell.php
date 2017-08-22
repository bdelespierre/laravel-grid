<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use OutOfBoundsException;
use InvalidArgumentException;

class Cell extends Model
{
    use Concerns\HasVersion,
        Concerns\HasUuid;

    protected $fillable = ['x', 'y', 'data'];

    protected $casts = ['x' => 'integer', 'y' => 'integer', 'data' => 'array'];

    public $autocommit = true;

    public function getCoordinatesAttribute()
    {
        return [$this->x, $this->y];
    }

    public function getAdjacentsAttribute()
    {
        return $this->getAdjacents();
    }

    public function grid()
    {
        return $this->belongsTo(Grid::class);
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
        $this->data = $data;

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
        $x   = $this->x;
        $y   = $this->y;

        switch ($dir) {
            case 'n'  : case 'north'     : list($x , $y) = [$x   , $y-1]; break;
            case 'ne' : case 'northeast' : list($x , $y) = [$x+1 , $y-1]; break;
            case 'e'  : case 'east'      : list($x , $y) = [$x+1 , $y  ]; break;
            case 'se' : case 'southeast' : list($x , $y) = [$x+1 , $y+1]; break;
            case 's'  : case 'south'     : list($x , $y) = [$x   , $y+1]; break;
            case 'sw' : case 'southwest' : list($x , $y) = [$x-1 , $y+1]; break;
            case 'w'  : case 'west'      : list($x , $y) = [$x-1 , $y  ]; break;
            case 'nw' : case 'northwest' : list($x , $y) = [$x-1 , $y-1]; break;

            default:
                throw new InvalidArgumentException("Invalid direction: $direction");
        }

        try {
            return $this->grid->at($x, $y);
        } catch (OutOfBoundsException $e) {
            return null;
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
