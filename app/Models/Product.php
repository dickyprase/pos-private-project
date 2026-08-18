<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_available' => 'boolean', 'is_favorite' => 'boolean', 'base_price' => 'integer', 'cost_estimate' => 'integer'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }
}
