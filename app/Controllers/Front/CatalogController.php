<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;

class CatalogController extends Controller
{
    public function index(): void
    {
        $filters = [
            'category' => $_GET['kategori'] ?? null,
            'tag' => $_GET['etiket'] ?? null,
            'q' => $_GET['q'] ?? null,
            'sort' => $_GET['sirala'] ?? null,
        ];

        $products = Product::search($this->pdo, $filters);
        $categories = Category::all($this->pdo);
        $tags = Tag::all($this->pdo);

        $this->view->render('front/catalog', [
            'title' => 'Katalog',
            'products' => $products,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $filters,
            'metaDescription' => 'Dijital ürün katalogu - E-PIN, hesap ve abonelik seçenekleri.',
        ]);
    }
}
