<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index()
    {
        $products = Cache::remember('products.all', 60, function () {
        return Product::with('store')->latest()->get();
    });

    return response()->json($products);
    }

    public function show(Product $product)
    {
        $cachedProduct = Cache::remember("products.{$product->id}", 60, function () use ($product) {
        return $product->load('store');
    });

    return response()->json($cachedProduct);
    }

    public function store(Request $request)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::create($validator->validated());
        Cache::forget('products.all');

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);

    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeRoles(['admin', 'employee']);

        if (auth()->user()->role === 'employee') {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:0',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'quantity' => 'sometimes|required|integer|min:0',
            ]);
        }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product->update($validator->validated());
        Cache::forget('products.all');
        Cache::forget("products.{$product->id}");

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);

    }

    public function destroy(Product $product)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $product->delete();
        Cache::forget('products.all');
        Cache::forget("products.{$product->id}");

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    protected function authorizeRoles(array $roles)
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, $roles) || $user->status !== 'approved') {
            abort(403, 'Forbidden');
        }
    }
}
