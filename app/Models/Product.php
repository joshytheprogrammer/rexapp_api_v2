<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'category_id',
        'manufacturer',
        'description',
        'partNumber',
        'specification',
        'rating',
        'min_price',
        'max_price',
        'imageURL',
        'slug',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
