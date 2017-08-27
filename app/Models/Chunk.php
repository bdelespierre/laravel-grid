<?php

namespace App\Models;

use Generator;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class Chunk extends Model
{
    use Concerns\HasVersion,
        Concerns\HasUuid,
        Concerns\HasData;

    protected $fillable = ['x', 'y', 'size', 'data'];

    protected $casts = [
        'x'    => 'integer',
        'y'    => 'integer',
        'size' => 'integer',
        'data' => 'array',
    ];

    public function __toString()
    {
        return vsprintf("[\d:\d],[\d:\d]", $this->getRectAttribute());
    }

    public function grid()
    {
        return $this->belongsTo(Grid::class);
    }

    public function cells()
    {
        list($x1,$y1,$x2,$y2) = $this->getRectAttribute();
        return $this->grid->cells()
            ->whereBetween('x', [$x1, $x2])
            ->whereBetween('y', [$y1, $y2]);
    }

    public function getRectAttribute(): array
    {
        return [$this->x, $this->y, $this->x + $this->size -1, $this->y + $this->size -1];
    }
}
