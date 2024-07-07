<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function getOrders()
    {
        try {
            $userId = Auth::id();

            $user = User::with('orders')->find($userId);

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            // Sort the user's orders by date/time ordered and status
            $orders = $user->orders->sortByDesc('orderDate')->values()->all();

            return response()->json(['orders' => $orders], 200);
        } catch (\Exception $e) {
            // \Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching orders.'], 500);
        }
    }

    public function getOrderById($id)
    {
        $userId = Auth::id();

        if (!is_numeric($id)) {
            return response()->json(['message' => 'Invalid order ID.'], 400);
        }

        try {
            $user = User::with(['orders' => function($query) use ($id) {
                $query->where('id', $id);
            }])->find($userId);

            if (!$user || $user->orders->isEmpty()) {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            $order = $user->orders->first();

            $order['items'] = $order->items;

            return response()->json($order, 200);
        } catch (\Exception $e) {
            // \Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching the order.'], 500);
        }
    }
}
