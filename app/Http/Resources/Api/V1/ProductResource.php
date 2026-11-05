<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'weight_kg' => $this->weight_kg,
            'dimensions' => [
                'height_cm' => $this->height_cm,
                'width_cm' => $this->width_cm,
                'length_cm' => $this->length_cm,
            ],
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
