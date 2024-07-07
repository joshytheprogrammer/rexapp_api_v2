<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchController;

use App\Http\Middleware\ValidateToken;
use App\Http\Middleware\AdminMiddleware;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/signup', 'signup');
    Route::get('/me', 'me')->middleware(ValidateToken::class);

    Route::post('/refresh', 'refresh');
    Route::post('/logout', 'logout');
});

Route::prefix('user')->middleware(ValidateToken::class)->group(function () {
    Route::get('/profile', [UserProfileController::class, 'getProfile']);
    Route::post('/change-name', [UserProfileController::class, 'changeName']);
    Route::post('/change-username', [UserProfileController::class, 'changeUsername']);
    Route::post('/change-email', [UserProfileController::class, 'changeEmail']);
    Route::post('/change-password', [UserProfileController::class, 'changePassword']);
    Route::post('/change-address', [UserProfileController::class, 'changeAddress']);
});

Route::post('/forgot-password', [UserProfileController::class, 'forgotPassword']);
Route::post('/reset-password', [UserProfileController::class, 'resetPassword']);

Route::controller(ProductController::class)->group(function () {
    Route::get('/products/random', 'getRandom');
    Route::get('/products/recent', 'getRecent');
    Route::get('/products/byId/{id}', 'getById');
    Route::get('/products/bySlug/{slug}', 'getBySlug');
    Route::get('/products/byCatId/{categoryId}', 'getByCatId');
});


Route::middleware(ValidateToken::class)->group(function () {
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/remove', [CartController::class, 'remove']);
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity']);
    Route::post('/cart/sync', [CartController::class, 'syncCart']);
    Route::post('/cart/checkout', [CartController::class, 'checkout']);
});

Route::controller(CategoryController::class)->group(function () {
    Route::get('/categories/all', 'getAll');
    Route::get('/categories/byId/{id}', 'getById');
    Route::get('/categories/bySlug/{slug}', 'getBySlug');
});

Route::controller(GuestController::class)->group(function () {
    Route::post('/guest/calculateSubtotal', 'calculateSubtotal');
});

Route::controller(SearchController::class)->group(function () {
    Route::get('/search/exec', 'execute');
});

Route::middleware(ValidateToken::class)->controller(OrderController::class)->group(function () {
    Route::get('/orders', 'getOrders');
    Route::get('/orders/byId/{id}', 'getOrderById');
});

Route::prefix('admin')->middleware([ValidateToken::class, AdminMiddleware::class])->controller(AdminController::class)->group(function () {
    Route::post('/auth/login', 'login')->withoutMiddleware([ValidateToken::class, AdminMiddleware::class]);
    Route::post('/auth/logout', 'logout')->withoutMiddleware([ValidateToken::class, AdminMiddleware::class]);

    Route::post('/create/product', 'createProduct');
    Route::post('/create/category', 'createCategory');

    Route::post('/edit/product/byId', 'editProductById');
    Route::post('/edit/category/byId', 'editCategoryById');

    Route::post('/delete/product/byId', 'deleteProductById');
    Route::post('/delete/category/byId', 'deleteCategoryById');

    Route::get('/view/categories/all', 'getAllCategories');
    Route::get('/view/users', 'getUsers');
    Route::get('/view/products', 'getProducts');
    Route::get('/view/products/byId/{id}', 'getProductById');
    Route::get('/view/categories', 'getCategories');
    Route::get('/view/categories/byId/{id}', 'getCategoryById');
    Route::get('/view/orders', 'getOrders');
    Route::get('/view/orders/{id}', 'getOrderById');
    Route::post('/view/order/complete', 'completeOrder');
    Route::get('/view/analytics', 'getAnalytics');
});
