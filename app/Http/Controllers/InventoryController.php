<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function index()
    {
        return response()->json(Inventory::with(['product', 'user'])->get());
    }

    public function store(Request $request)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $inventory = Inventory::create(array_merge(
            $validator->validated(),
            ['user_id' => auth()->id()]
        ));

        return response()->json([
            'message' => 'Inventory record created successfully',
            'inventory' => $inventory->load(['product', 'user']),
        ], 201);
    }

    public function update(Request $request, Inventory $inventory)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $inventory->update($validator->validated());

        return response()->json([
            'message' => 'Inventory record updated successfully',
            'inventory' => $inventory->load(['product', 'user']),
        ]);
    }

    public function destroy(Inventory $inventory)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $inventory->delete();

        return response()->json([
            'message' => 'Inventory record deleted successfully',
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