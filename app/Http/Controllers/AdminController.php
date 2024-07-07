<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Order;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $user = User::where('email', $request->email)->first();
        // return response()->json($user->password);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid username or password!'], 401);
        }

        if (!$user->is_admin) {
            return response()->json(['message' => 'Access denied: Only admin users can log in'], 403);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'accessToken' => $token
        ], 200);
    }

    public function createProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'manufacturer' => 'required|string',
            'description' => 'required|string',
            'partNumber' => 'nullable|string',
            'specification' => 'nullable|string',
            'rating' => 'nullable|numeric',
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
            'imageURL' => 'required|string',
            'slug' => 'required|string|unique:products,slug'
        ]);

        try {
            $product = new Product([
                'name' => $request->name,
                'category_id' => $request->categories,
                'manufacturer' => $request->manufacturer,
                'description' => $request->description,
                'partNumber' => $request->partNumber,
                'specification' => $request->specification,
                'rating' => $request->rating ?? 4,
                'min_price' =>$request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'imageURL' => $request->imageURL,
                'slug' => $request->slug
            ]);

            $product->save();

            return response()->json(['message' => 'Product created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the product', 'error' => $e->getMessage()], 500);
        }
    }

    public function createCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:categories,slug',
            'imageURL' => 'required|string',
            'description' => 'nullable|string'
        ]);

        try {
            $category = new Category([
                'name' => $request->name,
                'slug' => $request->slug,
                'imageURL' => $request->imageURL,
                'description' => $request->description
            ]);

            $category->save();

            return response()->json(['message' => 'Category created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the category', 'error' => $e->getMessage()], 500);
        }
    }

    public function editProductById(Request $request)
    {
        $request->validate([
            'product' => 'required|array'
        ]);

        try {
            $productData = $request->input('product');
            $product = Product::findOrFail($productData['id']);
            $product->update($productData);

            return response()->json(['message' => 'Product updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the product.', 'error' => $e->getMessage()], 500);
        }
    }

    public function editCategoryById(Request $request)
    {
        $request->validate([
            'category' => 'required|array'
        ]);

        try {
            $categoryData = $request->input('category');
            $category = Category::findOrFail($categoryData['id']);
            $category->update($categoryData);

            return response()->json(['message' => 'Category updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the category.', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteProductById(Request $request)
    {
        $request->validate([
            'productId' => 'required|exists:products,id'
        ]);

        try {
            $productId = $request->input('productId');
            Product::findOrFail($productId)->delete();

            return response()->json(['message' => 'Product deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the product.', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteCategoryById(Request $request)
    {
        $request->validate([
            'categoryId' => 'required|exists:categories,id'
        ]);

        try {
            $categoryId = $request->input('categoryId');
            Category::findOrFail($categoryId)->delete();

            return response()->json(['message' => 'Category deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the category.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllCategories()
    {
        try {
            $categories = Category::orderBy('name', 'asc')->get(['id', 'name']);
            return response()->json($categories, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching categories.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getUsers()
    {
        try {
            $users = User::where('is_admin', 0)->get();

            $users->transform(function ($user) {
                $user->cartLength = $user->cart ? $user->cart->count() : 0;
                $user->ordersLength = $user->orders ? $user->orders->count() : 0;
                return $user;
            });

            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching users.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getProducts()
    {
        try {
            $products = Product::all();
            return response()->json(['products' => $products], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching products.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getProductById($id) {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json(['message' => 'No product found with that ID!'], 200);
            }

            return response()->json(['product' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the product.'], 500);
        }
    }

    public function getCategories()
    {
        try {
            $categories = Category::all();
            return response()->json(['categories' => $categories], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching categories.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getCategoryById($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json(['message' => 'No category found with that ID!'], 404);
            }

            return response()->json(['category' => $category], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the category.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getOrders()
    {
        try {
            $orders = Order::whereHas('user', function ($query) {
                $query->where('is_admin', false);
            })->get();

            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found for non-admin users.'], 404);
            }

            return response()->json(['orders' => $orders], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching orders.', 'error' => $e->getMessage()], 500);
        }
    }


    public function getOrderById($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            $user = $order->user;

            $order->itemsLength = $order->items ? $order->items->count() : 0;

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return response()->json(['order' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the order.', 'error' => $e->getMessage()], 500);
        }
    }


    public function completeOrder(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',
            'orderId' => 'required|exists:orders,id'
        ]);

        try {
            $user = User::findOrFail($request->input('userId'));
            $order = $user->orders()->findOrFail($request->input('orderId'));

            $order->status = 'completed';
            $order->save();

            // Assume sendFulfillMessage is a method to send the email
            // $this->sendFulfillMessage($user->username, $user->email, $order->id);

            return response()->json(['message' => 'Order marked as completed.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while completing the order.', 'error' => $e->getMessage()], 500);
        }
    }


    public function getAnalytics()
    {
        try {
            $analytics = $this->calculateAnalytics();
            return response()->json($analytics, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while calculating analytics.', 'error' => $e->getMessage()], 500);
        }
    }

    private function calculateAnalytics()
    {
        // Calculate order analytics
        $orderAnalytics = Order::selectRaw('
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pendingOrders,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completedOrders,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelledOrders
        ')->first();

        // Calculate user analytics
        $userAnalytics = User::where('is_admin', false)->count();

        // Calculate product analytics
        $productAnalytics = Product::count();

        // Calculate category analytics
        $categoryAnalytics = Category::count();

        return [
            'pendingOrders' => $orderAnalytics->pendingOrders,
            'completedOrders' => $orderAnalytics->completedOrders,
            'cancelledOrders' => $orderAnalytics->cancelledOrders,
            'registeredUsers' => $userAnalytics,
            'totalProducts' => $productAnalytics,
            'totalCategories' => $categoryAnalytics,
        ];
    }

    private function sendFulfillMessage($username, $email, $orderId)
    {
        $emailDetails = [
            'to' => $email,
            'subject' => "Goodday $username",
            'html' => "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Rexapp update</title>
            </head>
            <body>
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                  <p>This mail is to inform you that the order - $orderId has had its status changed to completed.</p>
                  <p>No action is required from you.</p>
                  <p>This is an autogenerated email, please do not reply here.</p>
                </div>
            </body>
            </html>
            "
        ];

        // Assume sendMail is a method to send the email
        $this->sendMail($emailDetails);
    }

    private function sendMail($emailDetails)
    {
        // Logic to send email
    }
}
