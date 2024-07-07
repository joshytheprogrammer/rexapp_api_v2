<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Mail;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $user = $request->user;
        $partId = $request->partId;
        $quantity = $request->quantity;

        $product = Product::find($partId);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $cartItem = $user->cart()->where('product_id', $partId)->first();
        if ($cartItem) {
            $cartItem->quantity += $quantity;
        } else {
            $user->cart()->create([
                'product_id' => $partId,
                'quantity' => $quantity,
            ]);
        }

        $user->save();
        return response()->json(['message' => 'Product added to cart successfully.'], 200);
    }

    public function remove(Request $request)
    {
        $user = $request->user;
        $partId = $request->partId;

        $cartItem = $user->cart()->where('product_id', $partId)->first();
        if (!$cartItem) {
            return response()->json(['message' => 'Product not found in cart.'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Product removed from cart successfully.'], 200);
    }

    public function getCart(Request $request)
    {
        $user = $request->user;
        return response()->json(['cart' => $user->cart], 200);
    }

    public function updateQuantity(Request $request)
    {
        $user = $request->user;
        $partId = $request->partId;
        $quantity = $request->quantity;

        $cartItem = $user->cart()->where('product_id', $partId)->first();
        if (!$cartItem) {
            return response()->json(['message' => 'Product not found in cart.'], 404);
        }

        $cartItem->quantity = $quantity;
        $cartItem->save();
        return response()->json(['message' => 'Cart item quantity updated successfully.'], 200);
    }

    public function syncCart(Request $request)
    {
        $user = $request->user;
        $cart = $request->cart;

        $user->cart()->delete();
        foreach ($cart as $item) {
            $user->cart()->create([
                'product_id' => $item['partId'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json(['message' => 'Cart synced successfully.'], 200);
    }

    public function checkout(Request $request)
    {
        $user = $request->user;
        $cart = $request->cart;

        if (!$cart || !is_array($cart) || count($cart) === 0) {
            return response()->json(['message' => 'Invalid cart format.'], 400);
        }

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        foreach ($cart as $item) {
            $order->items()->create([
                'product_id' => $item['partId'],
                'quantity' => $item['quantity'],
            ]);
        }

        $user->cart()->delete();

        // $this->sendCheckoutMail($user, $order->id);
        return response()->json(['message' => 'Order placed successfully.', 'orderId' => $order->id], 200);
    }

    private function sendCheckoutMail($user, $orderId)
    {
        $order = Order::find($orderId);
        $emailDetails = [
            'to' => $user->email,
            'subject' => 'Thank you for your order',
            'html' => view('emails.order', ['user' => $user, 'order' => $order])->render(),
        ];

        Mail::send([], [], function ($message) use ($emailDetails) {
            $message->to($emailDetails['to'])
                ->subject($emailDetails['subject'])
                ->setBody($emailDetails['html'], 'text/html');
        });

        $adminEmailDetails = [
            'to' => 'admin@rexapp.ng',
            'subject' => 'Attention required!!!',
            'html' => view('emails.admin_order', ['order' => $order])->render(),
        ];

        Mail::send([], [], function ($message) use ($adminEmailDetails) {
            $message->to($adminEmailDetails['to'])
                ->subject($adminEmailDetails['subject'])
                ->setBody($adminEmailDetails['html'], 'text/html');
        });
    }
}
