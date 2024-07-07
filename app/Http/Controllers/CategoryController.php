<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Search;
use Illuminate\Http\Request;
use PDO;

class CategoryController extends Controller
{
    public function getAll(Request $request)
    {
        try {
            $limit = $request->query('limit');

            if($limit == null){

            }
            else if (!is_numeric($limit) || $limit <= 0) {
                return response()->json(['message' => 'Invalid limit parameter.'], 400);
            }

            $aggregationPipeline = Category::query();

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

            if ($limit !== null) {
                $categories = $aggregationPipeline->limit($limit)->get();
            } else {
                $categories = $aggregationPipeline->get();
            }


            if ($categories->isEmpty()) {
                return response()->json(['message' => 'No categories found.'], 200);
            }

            return response()->json(['categories' => $categories], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching categories.'], 500);
        }
    }

    public function getById($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json(['message' => 'Category not found.'], 400);
            }

            return response()->json(['category' => $category], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the category.'], 500);
        }
    }

    public function getBySlug($slug, Request $request)
    {
        try {
            $category = Category::where('slug', $slug)->first();

            if (!$category) {
                return response()->json(['message' => 'Category not found.'], 400);
            }

            if ($request->has('sID')) {
                $search = Search::find($request->get('sID'));
                if ($search) {
                    $search->visited_category_id = $category->id;
                    $search->save();
                }
            }

            return response()->json(['category' => $category], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the category.', 'details' => $e], 500);
        }
    }
}
