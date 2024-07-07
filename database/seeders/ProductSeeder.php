<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = json_decode(File::get(database_path('/data/products.json')), true);
        $categories = json_decode(File::get(database_path('/data/categories.json')), true);

        // Create a mapping from old category IDs to new category IDs
        $categoryMapping = [];
        foreach ($categories as $index => $category) {
            $categoryMapping[$category['_id']] = $index + 1; // Assuming IDs are 1-based
        }

        foreach ($products as $product) {
            $category_id = isset($product['categories'][0]) ? $categoryMapping[$product['categories'][0]] : 1;

            DB::table('products')->insert([
                'name' => $product['name'],
                'category_id' => $category_id,
                'manufacturer' => $product['manufacturer'],
                'description' => $product['description'],
                'partNumber' => $product['partNumber'],
                'specification' => $product['specification'],
                'rating' => $product['rating'],
                'min_price' => $product['price']['min'],
                'max_price' => $product['price']['max'],
                'imageURL' => $product['imageURL'],
                'slug' => $product['slug'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
