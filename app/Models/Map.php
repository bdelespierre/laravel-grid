<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Map extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['name', 'width', 'height'];
    
    protected $dates = ['deleted_at'];
    
    public function cells()
    {
        return $this->hasMany(Cell::class);
    }
    
    public function at($x, $y)
    {
        return $this->cells()->where('x', $x)->where('y', $y)->get();
    }
    
    public function getAt($x, $y, $key, $default = null)
    {
        return array_get($this->at($x, $y)->data, $default);
    }
    
    public function setAt($x, $y, $key, $value)
    {
        return array_set($this->at($x, $y)->data, $key, $value);
    }
}
