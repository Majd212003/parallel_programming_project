<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOrderInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $orderId)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::with(['user', 'orderItems.product', 'payment'])
            ->find($this->orderId);

        if (! $order) {
            return;
        }

        $invoiceData = [
            'order_id' => $order->id,
            'user_name' => $order->user?->name,
            'total_price' => $order->total_price,
            'payment_method' => $order->payment?->payment_method,
            'status' => $order->status,
            'items_count' => $order->orderItems->count(),
        ];

        logger()->info('Invoice generated in background', $invoiceData);
    }
}
