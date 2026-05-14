<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json(Payment::with(['user', 'order'])->get());
    }

    public function show(Payment $payment)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json($payment->load(['user', 'order']));
    }
}
