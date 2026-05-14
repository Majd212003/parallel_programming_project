<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile()
    {
        return response()->json(auth()->user());
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|min:10|max:14|unique:users,phone_number,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update(array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => $request->password ? Hash::make($request->password) : null,
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function index()
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json(User::all());
    }

    public function show(User $user)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|min:10|max:14|unique:users,phone_number,' . $user->id,
            'role' => 'sometimes|required|in:admin,user,employee',
            'status' => 'sometimes|required|in:approved,pending,rejected',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
