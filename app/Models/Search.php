<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'searchTerm',
        'user_id',
        'visited_product_id',
        'visited_category_id',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visitedProduct()
    {
        return $this->belongsTo(Product::class, 'visited_product_id');
    }

    public function visitedCategory()
    {
        return $this->belongsTo(Category::class, 'visited_category_id');
    }
}
