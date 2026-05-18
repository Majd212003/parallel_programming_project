<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\LoadBalancerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrdersBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        $loadBalancer = new LoadBalancerService();
        Order::with(['user', 'payment'])
            ->chunkById(50, function ($orders) use ($loadBalancer) {

                foreach ($orders as $order) {

                    $server = $loadBalancer->getNextServer();

                    logger()->info('Order distributed to server', [
                        'server' => $server,
                        'order_id' => $order->id,
                        'order_status' => $order->status,
                        'is_paid' => $order->is_paid,
                        'payment_status' => $order->payment?->status,
                    ]);
                }
            });
    }
}
