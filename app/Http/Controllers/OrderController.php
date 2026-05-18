<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Jobs\GenerateOrderInvoice;

class OrderController extends Controller
{
    public function directOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            DB::transaction(function () use ($request, &$createdOrder) {
                $user = User::where('id', auth()->id())
                    ->lockForUpdate()
                    ->firstOrFail();

                $product = Product::where('id', $request->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->quantity < $request->quantity) {
                    throw new \RuntimeException(
                        'Store no longer contains enough stock for product: ' . $product->name
                    );
                }

                $totalPrice = $product->price * $request->quantity;

                if ((float) $user->wallet_balance < (float) $totalPrice) {
                    throw new \RuntimeException('Insufficient wallet balance');
                }

                $createdOrder = Order::create([
                    'user_id' => $user->id,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'is_paid' => false,
                ]);

                OrderItem::create([
                    'order_id' => $createdOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'total_amount' => $totalPrice,
                    'price_at_purchase' => $product->price,
                ]);

                $product->decrement('quantity', $request->quantity);
                $user->decrement('wallet_balance', $totalPrice);

                $createdOrder->update([
                    'status' => 'completed',
                    'is_paid' => true,
                ]);

                Payment::create([
                    'user_id' => $user->id,
                    'order_id' => $createdOrder->id,
                    'amount' => $totalPrice,
                    'payment_method' => $request->payment_method,
                    'status' => 'completed',
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $latestOrder = Order::with(['orderItems.product', 'payment'])
            ->latest()
            ->first();

        return response()->json([
            'message' => 'Direct order created successfully',
            'order' => $latestOrder,
            'wallet_balance' => auth()->user()->fresh()->wallet_balance,
        ], 201);
    }







    public function currentCart()
    {
        $order = $this->pendingOrder();

        if (! $order) {
            return response()->json([
                'cart' => null,
                'items' => [],
            ]);
        }

        return response()->json($order->load('orderItems.product'));
    }

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::find($request->product_id);

        if ($request->quantity > $product->quantity) {
            return response()->json([
                'message' => 'Requested quantity exceeds available Store',
            ], 422);
        }

        $order = $this->pendingOrder() ?? Order::create([
            'user_id' => auth()->id(),
            'total_price' => 0,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        $item = $order->orderItems()->where('product_id', $product->id)->first();
        $newQuantity = $request->quantity;

        if ($item) {
            $newQuantity = $item->quantity + $request->quantity;
        }

        if ($newQuantity > $product->quantity) {
            return response()->json([
                'message' => 'Requested quantity exceeds available Store',
            ], 422);
        }

        if ($item) {
            $item->quantity = $newQuantity;
        } else {
            $item = new OrderItem([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price_at_purchase' => $product->price,
                'total_amount' => $request->quantity * $product->price
            ]);
            $order->orderItems()->save($item);
        }

        // $item->total_amount = $item->quantity * $item->price_at_purchase;
       // $item->save();

        $this->updateOrderTotals($order);

        return response()->json([
            'message' => 'Product added to cart',
            'cart' => $order->load('orderItems.product'),
        ], 201);
    }

    public function updateCartItem(Request $request, OrderItem $item)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = $this->pendingOrder();
        abort_if(! $order || $item->order_id !== $order->id, 404, 'Cart item not found');

        $product = Product::find($item->product_id);
        if ($request->quantity > $product->quantity) {
            return response()->json([
                'message' => 'Requested quantity exceeds available Store',
            ], 422);
        }

        $item->quantity = $request->quantity;
        $item->total_amount = $item->quantity * $item->price_at_purchase;
        $item->save();

        $this->updateOrderTotals($order);

        return response()->json([
            'message' => 'Cart item updated successfully',
            'cart' => $order->load('orderItems.product'),
        ]);
    }

    public function removeCartItem(OrderItem $item)
    {
        $order = $this->pendingOrder();
        abort_if(! $order || $item->order_id !== $order->id, 404, 'Cart item not found');

        $item->delete();

        if ($order->orderItems()->count() === 0) {
            $order->delete();

            return response()->json([
                'message' => 'Cart cleared',
                'cart' => null,
            ]);
        }

        $this->updateOrderTotals($order);

        return response()->json([
            'message' => 'Cart item removed successfully',
            'cart' => $order->load('orderItems.product'),
        ]);
    }

    public function confirmCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = $this->pendingOrder();

        abort_if(
            ! $order || $order->orderItems()->count() === 0,
            404,
            'No active cart found'
        );

        $this->updateOrderTotals($order);

        $confirmedOrderId = $order->id;

        try {
            DB::transaction(function () use ($request, $confirmedOrderId) {

                // lock user row
                $user = User::where('id', auth()->id())
                    ->lockForUpdate()
                    ->firstOrFail();

                // lock order row
                $order = Order::where('id', $confirmedOrderId)
                    ->with(['orderItems.product'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // check stock with locked products
                foreach ($order->orderItems->sortBy('product_id') as $item) {
                    $product = Product::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($product->quantity < $item->quantity) {
                        throw new \RuntimeException(
                            'Store no longer contains enough stock for product: ' . $product->name
                        );
                    }
                }

                // wallet check
                if ((float) $user->wallet_balance < (float) $order->total_price) {
                    throw new \RuntimeException('Insufficient wallet balance');
                }

                // decrement stock
                foreach ($order->orderItems->sortBy('product_id') as $item) {
                    $product = Product::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $product->decrement('quantity', $item->quantity);
                }

                // decrement wallet
                $user->decrement('wallet_balance', $order->total_price);

                // complete order
                $order->update([
                    'status' => 'completed',
                    'is_paid' => true,
                ]);

                // create payment
                Payment::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $order->total_price,
                    'payment_method' => $request->payment_method,
                    'status' => 'completed',
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $latestOrder = Order::with(['orderItems.product', 'payment'])
            ->where('id', $confirmedOrderId)
            ->first();
        GenerateOrderInvoice::dispatch($latestOrder->id);


        return response()->json([
            'message' => 'Cart confirmed and order completed',
            'order' => $latestOrder,
            'wallet_balance' => auth()->user()->fresh()->wallet_balance,
        ]);
    }

    public function userOrders()
    {
        return response()->json(
            Order::with(['orderItems.product', 'payment'])
                ->where('user_id', auth()->id())
                ->get()
        );
    }

    public function show(Order $order)
    {
        $user = auth()->user();
        abort_if($user->role !== 'admin' && $order->user_id !== $user->id, 403, 'Forbidden');

        return response()->json($order->load(['orderItems.product', 'payment']));
    }

    public function adminOrders()
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json(
            Order::with(['user', 'orderItems.product', 'payment'])
                ->get()
        );
    }

    protected function pendingOrder()
    {
        return Order::where('user_id', auth()->id())
            ->where('is_paid', false)
            ->first();
    }

    protected function updateOrderTotals(Order $order)
    {
        $total = $order->orderItems()->sum('total_amount');
        $order->update([
            'total_price' => $total,
        ]);
    }
}
