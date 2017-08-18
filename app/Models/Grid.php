<?php

namespace App\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OutOfBoundsException;
use LogicException;

class Grid extends Model implements Arrayable
{
    use SoftDeletes,
        Concerns\HasUuid;

    protected $fillable = ['name', 'width', 'height'];

    protected $dates = ['deleted_at'];

    public function __toString()
    {
        return (string) view('models.grid', ['grid' => $this]);
    }

    public function getInfiniteAttribute()
    {
        return $this->width == -1 || $this->height == -1;
    }

    public function cells()
    {
        return $this->hasMany(Cell::class);
    }

    public function at($x, $y)
    {
        if ($this->width != -1 && ($x < 0 || $x >= $this->width)) {
            throw new OutOfBoundsException;
        }

        if ($this->height != -1 && ($y < 0 || $y >= $this->height)) {
            throw new OutOfBoundsException;
        }

        return $this->cells()->where('x', $x)->where('y', $y)->first()
            ?: $this->cells()->create(compact('x', 'y'));
    }

    public function offsetGet($offset)
    {
        if (is_string($offset) && preg_match('/(\d+)\s*(:|x|-|,)\s*(\d+)/', $offset, $matches)) {
            list(, $x,, $y) = $matches;
            return $this->at($x, $y);
        }

        return parent::offsetGet($offset);
    }

    public function toArray(): array
    {
        if ($this->infinite) {
            throw new LogicException("Cannot convert infinite grid to array");
        }

        $grid = [];

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $grid[$x][$y] = $this->at($x, $y);
            }
        }

        return $grid;
    }
}
