<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. التحقق من البيانات (الخط الدفاعي الأول ضد التضارب)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number'=> 'sometimes|string|min:10|max:14|unique:users,phone_number',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,employee,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. إنشاء المستخدم
        // ملاحظة: قاعدة البيانات ستحمينا أيضاً بفضل قيد الـ Unique على الإيميل
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->role === 'employee' ? 'pending' : 'approved',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;



        return response()->json([
            'message' => 'User registered successfully' ,
            'token' => $token,
        ], 201);
    }

    /**
     * تسجيل الدخول
     */
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->status !== 'approved') {
            return response()->json([
                'message' => 'Account is not approved yet'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
        ]);
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        // حذف التوكن الحالي لإنهاء الجلسة
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}