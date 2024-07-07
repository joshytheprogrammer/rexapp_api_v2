<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Search;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function getRandom(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            if (!is_numeric($limit) || $limit <= 0) {
                return response()->json(['message' => 'Invalid limit parameter.'], 400);
            }

            $aggregationPipeline = Product::query();

            if ($request->query('filters')) {
                $filters = json_decode($request->query('filters'), true);
                $aggregationPipeline->where($filters);
            }

            if ($request->query('sort')) {
                $sortField = $request->query('sort');
                $aggregationPipeline->orderBy($sortField, 'asc');
            }

            if ($request->query('fields')) {
                $fields = explode(',', $request->query('fields'));
                $aggregationPipeline->select($fields);
            }

            $products = $aggregationPipeline->inRandomOrder()->limit($limit)->get();

            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found.'], 200);
            }

            return response()->json(['products' => $products], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching products.'], 500);
        }
    }

    public function getRecent(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            if (!is_numeric($limit) || $limit <= 0) {
                return response()->json(['message' => 'Invalid limit parameter.'], 400);
            }

            $products = Product::orderBy('created_at', 'desc')->limit($limit)->get();

            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found.'], 200);
            }

            return response()->json(['products' => $products], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching products.'], 500);
        }
    }

    public function getById(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json(['message' => 'No product found with that ID!'], 200);
            }

            // Cache the response
            Cache::put($request->fullUrl(), ['product' => $product], now()->addMinutes(10));

            return response()->json(['product' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the product.'], 500);
        }
    }

    public function getBySlug(Request $request, $slug)
    {
        try {
            $product = Product::where('slug', $slug)->first();

            if (!$product) {
                return response()->json(['message' => 'No product found with that slug!'], 400);
            }

            // Cache the response
            Cache::put($request->fullUrl(), ['product' => $product], now()->addMinutes(10));

            if ($request->query('sID')) {
                $search = Search::find($request->query('sID'));

                if ($search) {
                    $search->visited_product_id = $product->id;
                    $search->save();
                }
            }

            return response()->json(['product' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the product.'], 500);
        }
    }

    public function getByCatId(Request $request, $categoryId)
    {
        try {
            if (!is_numeric($categoryId)) {
                return response()->json(['message' => 'Invalid category ID'], 400);
            }

            $products = Product::where('category_id', $categoryId)->get();

            return response()->json(['products' => $products], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching products'], 500);
        }
    }
}
