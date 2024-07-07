<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class GuestController extends Controller
{
    public function calculateSubtotal(Request $request)
    {
        try {
            $cart = $request->input('cart');

            if (!is_array($cart) || empty($cart)) {
                return response()->json(['message' => 'Invalid cart format.'], 400);
            }

            // Fetch the prices of products based on partIds
            $partIds = array_map(function($item) {
                return $item['partId'];
            }, $cart);



            $products = Product::whereIn('id', $partIds)->get();
            // Calculate the total minimum and maximum subtotals
            $minSubtotal = 0;
            $maxSubtotal = 0;

            foreach ($cart as $item) {

                $product = $products->firstWhere('id', $item['partId']);

                if ($product) {
                    // Account for the quantity in the cart
                    $minPrice = $product->min_price * $item['quantity'];
                    $maxPrice = $product->max_price * $item['quantity'];

                    $minSubtotal += $minPrice;
                    $maxSubtotal += $maxPrice;
                }
            }

            return response()->json(['minSubtotal' => $minSubtotal, 'maxSubtotal' => $maxSubtotal], 200);
        } catch (\Exception $e) {
            // \Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while calculating subtotals.'], 500);
        }
    }
}
