<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class World extends Model
{
    use Concerns\HasUuid,
        Concerns\HasData;

    protected $fillable = ['name'];

    public function __toString()
    {
        return (string) $this->name;
    }

    public function grids()
    {
        return $this->hasMany(Grid::class);
    }
}
