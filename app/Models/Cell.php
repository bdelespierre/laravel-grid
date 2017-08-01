<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cell extends Model
{
    protected $fillable = ['x', 'y', 'data'];
    
    protected $casts = ['data' => 'array'];
    
    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}
