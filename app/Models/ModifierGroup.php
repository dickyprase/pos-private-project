<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function options()
    {
        return $this->hasMany(ModifierOption::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
