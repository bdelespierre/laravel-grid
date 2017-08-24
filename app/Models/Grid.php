<?php

namespace App\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OutOfBoundsException;
use LogicException;
use InvalidArgumentException;

class Grid extends Model
{
    use SoftDeletes,
        Concerns\HasVersion,
        Concerns\HasUuid,
        Concerns\HasData;

    protected $fillable = ['name', 'width', 'height', 'data'];

    protected $dates = ['deleted_at'];

    protected $casts = ['width' => 'integer', 'height' => 'integer', 'data' => 'array'];

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

    public function chunks()
    {
        return $this->hasMany(Chunk::class);
    }

    public function at($x, $y): Cell
    {
        if ($this->width != -1 && ($x < 0 || $x >= $this->width)) {
            throw new OutOfBoundsException;
        }

        if ($this->height != -1 && ($y < 0 || $y >= $this->height)) {
            throw new OutOfBoundsException;
        }

        return $this->cells()->firstOrCreate(compact('x', 'y'));
    }

    public function rect($x1, $y1, $x2, $y2): array
    {
        $cells = [];

        if ($x2 - $x1 <= 0 && $y2 - $y1 <= 0) {
            throw new InvalidArgumentException("Not a rectangle: [$x1,$y1] [$x2,$y2]");
        }

        for ($x = $x1; $x <= $x1; $x++) {
            for ($y = $y1; $y < $y2; $y++) {
                $cells[$x][$y] = $this->at($x, $y);
            }
        }

        return $cells;
    }

    public function offsetGet($offset)
    {
        if (is_string($offset) && preg_match('/(\d+)\s*(:|x|-|,)\s*(\d+)/', $offset, $matches)) {
            list(, $x,, $y) = $matches;
            return $this->at($x, $y);
        }

        return parent::offsetGet($offset);
    }
}
