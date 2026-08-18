<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifierOption extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['price_adjustment' => 'integer', 'is_active' => 'boolean'];
    }

    public function group()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    public function recipes()
    {
        return $this->hasMany(ModifierRecipe::class);
    }
}
