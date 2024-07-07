<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('manufacturer');
            $table->text('description');
            $table->string('partNumber')->default('');
            $table->text('specification')->default('');
            $table->tinyInteger('rating')->unsigned()->default(1)->check(function ($column) {
                $column->between(1, 5);
            });
            $table->decimal('min_price', 8, 2);
            $table->decimal('max_price', 8, 2);
            $table->string('imageURL')->nullable();
            $table->string('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
