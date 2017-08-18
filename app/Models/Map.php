<?php

namespace App\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OutOfBoundsException;

class Map extends Model implements Arrayable
{
    use SoftDeletes,
        Concerns\HasUuid;

    protected $fillable = ['name', 'width', 'height'];

    protected $dates = ['deleted_at'];

    public function __toString()
    {
        return (string) view('models.map', ['map' => $this]);
    }

    public function cells()
    {
        return $this->hasMany(Cell::class);
    }

    public function at($x, $y)
    {
        if ($x < 0 || $x >= $this->width || $y < 0 || $y >= $this->height) {
            throw new OutOfBoundsException;
        }

        $cell = $this->cells()->where('x', $x)->where('y', $y)->first();

        if (!$cell) {
            $cell = $this->cells()->create(compact('x', 'y'));
        }

        return $cell;
    }

    public function offsetGet($offset)
    {
        if (is_string($offset) && preg_match('/^(\d+):(\d+)$/', $offset, $matches)) {
            list(, $x, $y) = $matches;
            return $this->at($x, $y);
        }

        return parent::offsetGet($offset);
    }

    public function toArray(): array
    {
        $map = [];

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $map[$x][$y] = $this->at($x, $y);
            }
        }

        return $map;
    }
}
