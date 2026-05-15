<?php

namespace App\Http\Controllers;


use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    public function index()
    {
        return response()->json(Store::with(['product', 'user'])->get());
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

        $Store = Store::create(array_merge(
            $validator->validated(),
            ['user_id' => auth()->id()]
        ));

        return response()->json([
            'message' => 'Store record created successfully',
            'Store' => $Store->load(['product', 'user']),
        ], 201);
    }

    public function update(Request $request, Store $Store)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $Store->update($validator->validated());

        return response()->json([
            'message' => 'Store record updated successfully',
            'Store' => $Store->load(['product', 'user']),
        ]);
    }

    public function destroy(Store $Store)
    {
        $this->authorizeRoles(['admin', 'employee']);

        $Store->delete();

        return response()->json([
            'message' => 'Store record deleted successfully',
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
