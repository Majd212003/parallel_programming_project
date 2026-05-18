<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function show(Product $product)
    {
        return response()->json($product);
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

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    public function destroy(Product $product)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $product->delete();

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
