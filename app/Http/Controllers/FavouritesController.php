<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class FavouritesController extends Controller
{
    public function index()
    {
    $userId = auth()->id();

        $favourites = Cache::remember("favourites.user.{$userId}", 60, function () use ($userId) {
            return Favourite::with('product')
                ->where('user_id', $userId)
                ->get();
        });
        return response()->json($favourites);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $favourite = Favourite::firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
        ]);
        Cache::forget("favourites.user." . auth()->id());

        return response()->json([
            'message' => 'Product added to favorites',
            'favorite' => $favourite->load('product'),
        ], 201);
    }

    public function destroy(Product $product)
    {
        $favorite = Favourite::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if (! $favorite) {
            return response()->json(['message' => 'Favorite entry not found'], 404);
        }

        $favorite->delete();
        Cache::forget("favourites.user." . auth()->id());

        return response()->json(['message' => 'Product removed from favorites']);
    }
}
