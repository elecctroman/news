<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Category;

class HomeController extends Controller
{
    public function index(): void
    {
        $featured = Product::featured($this->pdo, 6);
        $categories = Category::all($this->pdo);

        $this->view->render('front/home', [
            'title' => 'Dijital Mağaza',
            'featured' => $featured,
            'categories' => $categories,
            'metaDescription' => 'E-PIN, lisans ve sosyal hesap satışına yönelik hafif mağaza iskeleti.',
        ]);
    }
}
