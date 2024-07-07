<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Search;
use App\Helpers\Stemmer;
use App\Helpers\Stopwords;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function execute(Request $request)
    {
        try {
            $regex = '/[^a-zA-Z0-9_ ]/';
            $searchQuery = preg_replace($regex, '', $request->query('q'));

            $categoryId = $request->query('c');

            if (!$searchQuery || strlen($searchQuery) < 3) {
                return response()->json(['message' => 'Search query is required.'], 400);
            }

            $keywords = $this->splitQueryIntoKeywords($searchQuery);

            $productQuery = Product::query();
            foreach ($keywords as $keyword) {
                $productQuery->orWhere(function($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('manufacturer', 'like', "%{$keyword}%")
                        ->orWhere('imageURL', 'like', "%{$keyword}%")
                        ->orWhere('partNumber', 'like', "%{$keyword}%")
                        ->orWhere('specification', 'like', "%{$keyword}%");
                });
            }

            $categoryQuery = Category::query();
            foreach ($keywords as $keyword) {
                $categoryQuery->orWhere(function($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('imageURL', 'like', "%{$keyword}%");
                });
            }

            if ($categoryId && $categoryId !== 'all') {
                $productQuery->whereHas('categories', function($query) use ($categoryId) {
                    $query->where('id', $categoryId);
                });
                $categoryQuery->where('id', $categoryId);
            }

            $matchingProducts = $productQuery->get();
            $matchingCategories = $categoryQuery->get();

            $productsWithScore = $matchingProducts->map(function($product) use ($keywords) {
                return [
                    'product' => $product,
                    'score' => $this->calculateScore([
                        $product->name, $product->description, $product->partNumber, $product->specification, $product->imageURL
                    ], $keywords)
                ];
            });

            $categoriesWithScore = $matchingCategories->map(function($category) use ($keywords) {
                return [
                    'category' => $category,
                    'score' => $this->calculateScore([
                        $category->name, $category->description, $category->imageURL
                    ], $keywords)
                ];
            });

            $sortedProducts = $productsWithScore->sortByDesc('score')->values();
            $sortedCategories = $categoriesWithScore->sortByDesc('score')->values();

            $user = $request->user;

            $search = new Search();
            $search->searchTerm = $searchQuery;
            if($user){$search->user_id = $user['id'];}
            $search->visited_product_id = null;
            $search->visited_category_id = null;
            $search->save();

            $similarSearches = Search::where('searchTerm', $searchQuery)->get();

            $clickedProducts = $this->getClickedItems($similarSearches, Product::class);
            $clickedCategories = $this->getClickedItems($similarSearches, Category::class);

            $mergedProducts = $clickedProducts->merge($sortedProducts->pluck('product'));
            $mergedCategories = $clickedCategories->merge($sortedCategories->pluck('category'));

            $productFrequencyMap = $this->createFrequencyMap($mergedProducts);
            $categoryFrequencyMap = $this->createFrequencyMap($mergedCategories);

            $sortedMergedProducts = $this->sortByPopularity($mergedProducts, $productFrequencyMap);
            $sortedMergedCategories = $this->sortByPopularity($mergedCategories, $categoryFrequencyMap);

            $uniqueMergedProducts = $this->filterUniqueItems($sortedMergedProducts);
            $uniqueMergedCategories = $this->filterUniqueItems($sortedMergedCategories);

            return response()->json([
                'searchId' => $search->id,
                'products' => $uniqueMergedProducts,
                'categories' => $uniqueMergedCategories,
            ]);
        } catch (\Exception $e) {
            // \Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred while performing search'.$e->getMessage()], 500);
        }
    }

    private function splitQueryIntoKeywords($query)
{
    $stopwords = collect(Stopwords::get());
    $keywords = preg_match_all('/"([^"]+)"|\S+/', $query, $matches) ? $matches[0] : [];
    return collect($keywords)
        ->map(fn($token) => strtolower(Stemmer::stem($token)))
        ->filter(fn($token) => !$stopwords->contains($token))
        ->toArray();
}

    private function calculateScore($fields, $keywords)
    {
        $sanitizedFields = collect($fields)->map(fn($field) => $field ?? '');
        $sanitizedKeywords = collect($keywords)->map(fn($keyword) => $keyword ?? '');
        $keywordMatches = $sanitizedKeywords->filter(fn($keyword) =>
            $sanitizedFields->some(fn($field) => str_contains(strtolower($field), $keyword))
        );
        return $keywordMatches->count();
    }

    private function getClickedItems($similarSearches, $model)
    {
        return $similarSearches->pluck('visitedProductId')->unique()->filter()->map(function ($id) use ($model) {
            return $model::find($id);
        });
    }

    private function createFrequencyMap($items)
    {
        return $items->reduce(function ($map, $item) {
            $id = $item->id;
            if ($map->has($id)) {
                $map[$id] += 1;
            } else {
                $map[$id] = 1;
            }
            return $map;
        }, collect());
    }

    private function sortByPopularity($items, $frequencyMap)
    {
        return $items->sortByDesc(fn($item) => $frequencyMap[$item->id])->values();
    }

    private function filterUniqueItems($items)
    {
        return $items->unique(fn($item) => $item->id)->values();
    }
}
