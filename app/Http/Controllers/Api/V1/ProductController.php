<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductIndexRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $products = Product::query()
            ->active()
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->inCategory($slug))
            ->when($filters['name'] ?? null, fn (Builder $query, string $term) => $query->search($term))
            ->priceBetween($filters['min_price'] ?? null, $filters['max_price'] ?? null)
            ->with('categories')
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->active, 404);

        return new ProductResource($product->load('categories'));
    }
}
