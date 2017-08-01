<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OutOfBoundsException;

class Map extends Model
{
    use SoftDeletes,
        Concerns\HasUuid;

    protected $fillable = ['name', 'width', 'height'];

    protected $dates = ['deleted_at'];

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
}
