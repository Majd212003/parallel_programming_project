<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
        public function show()
    {
        return response()->json([
            'wallet_balance' => auth()->user()->wallet_balance,
        ]);
    }

    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();
        $user->increment('wallet_balance', $request->amount);
        $user->refresh();

        return response()->json([
            'message' => 'Wallet updated successfully',
            'wallet_balance' => $user->wallet_balance,
        ]);
    }
}
