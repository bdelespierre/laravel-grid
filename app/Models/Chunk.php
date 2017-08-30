<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
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
        return vsprintf("[%d:%d],[%d:%d]", $this->getRectAttribute());
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

    public function hasAdjacent($direction): bool
    {
        list($x, $y) = $this->getAdjacentCoordinates($direction);

        return 0 != count($this->grid->chunks()
            ->where('x',    '=', $x)
            ->where('y',    '=', $y)
            ->where('size', '=', $his->size)
            ->get()
        );
    }

    public function getAdjacent($direction): self
    {
        list($x, $y) = $this->getAdjacentCoordinates($direction);

        $chunk = $this->grid->chunks()
            ->where('x',    '=', $x)
            ->where('y',    '=', $y)
            ->where('size', '=', $his->size)
            ->first();

        if (!$chunk) {
            $chunk = new self(['x' => $x, 'y' => $y, 'size' => $this->size]);
            $this->grid->chunks()->save($chunk);
        }

        return $chunk;
    }

    public function getAdjacentCoordinates($direction): array
    {
        $d = strtolower(str_replace([' ', '-', ':'], '', $direction));
        $x = $this->x;
        $y = $this->y;
        $s = $this->size;

        switch ($d) {
            case 'n'  : case 'north'     : return [$x    , $y-$s];
            case 'ne' : case 'northeast' : return [$x+$s , $y-$s];
            case 'e'  : case 'east'      : return [$x+$s , $y   ];
            case 'se' : case 'southeast' : return [$x+$s , $y+$s];
            case 's'  : case 'south'     : return [$x    , $y+$s];
            case 'sw' : case 'southwest' : return [$x-$s , $y+$s];
            case 'w'  : case 'west'      : return [$x-$s , $y   ];
            case 'nw' : case 'northwest' : return [$x-$s , $y-$s];

            default:
                throw new InvalidArgumentException("Invalid direction: $direction");
        }
    }

    public function flushCells(): bool
    {
        return 0 == Artisan::call('chunk:flush', ['chunk' => $this->id]);
    }

    public function fillWithEmptyCells(): bool
    {
        return 0 == Artisan::call('chunk:fill',  ['chunk' => $this->id]);
    }

    public function terraform(): bool
    {
        return 0 == Artisan::call('chunk:terraform', ['chunk' => $this->id]);
    }
}
