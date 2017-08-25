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

    protected $fillable = ['x1', 'y1', 'x2', 'y2', 'data'];

    protected $casts = [
        'x1' => 'integer',
        'y1' => 'integer',
        'x2' => 'integer',
        'y2' => 'integer',
    ];

    public function __toString()
    {
        return "[{$this->x1}:{$this->y1}],[{$this->x2}:{$this->y2}]";
    }

    public function grid()
    {
        return $this->belongsTo(Grid::class);
    }

    public function cells()
    {
        return $this->grid->cells()
            ->whereBetween('x', [$this->x1, $this->x2])
            ->whereBetween('y', [$this->y1, $this->y2]);
    }

    public function getRectAttribute(): array
    {
        return [$this->x1, $this->y1, $this->x2, $this->y2];
    }
}
