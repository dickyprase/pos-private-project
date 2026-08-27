<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'category_id' => $this->category_id, 'category_slug' => $this->whenLoaded('category', fn () => $this->category->slug), 'name' => $this->name, 'slug' => $this->slug, 'sku' => $this->sku, 'description' => $this->description, 'image_path' => $this->image_path, 'image_url' => $this->image_path ? url('/storage/'.$this->image_path) : null, 'base_price' => $this->base_price, 'is_available' => $this->is_available, 'is_favorite' => (bool) $this->is_favorite, 'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'price_adjustment' => $v->price_adjustment, 'is_default' => $v->is_default])), 'modifier_groups' => $this->whenLoaded('modifierGroups', fn () => $this->modifierGroups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'min_selection' => $g->min_selection, 'max_selection' => $g->max_selection, 'is_required' => (bool) $g->is_required, 'options' => $g->options->where('is_active', true)->values()->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'price_adjustment' => $o->price_adjustment])]))];
    }
}
