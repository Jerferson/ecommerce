<?php

use Dale\Model\Cart;
use Dale\Model\Category;
use Dale\Model\Product;
use Dale\Page;

/**
 * @route(/)
 */
$app->get('/', function () {

    $products = Product::listAll();

    $page = new Page();
    $page->setTpl("index", [
        'products' => Product::checkList($products)
    ]);
});

/**
 * @route(/categories/idcategory:\d+)
 * 
 * @param int $idcategory
 */
$app->get('/categories/:idcategory', function ($idcategory) {

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $category = new Category();
    $category->get((int)$idcategory);

    $pagination = $category->getProductsPage($page);

    $pages = [];

    for ($i = 1; $i <= $pagination['pages']; $i++) {
        array_push($pages, [
            'link' => '/categories/' . $category->getidcategory() . '?page=' . $i,
            'page' => $i
        ]);
    }

    $page = new Page();
    $page->setTpl("category", [
        'category' => $category->getValues(),
        'products' => $pagination['data'],
        'pages' => $pages
    ]);
});

/**
 * @route(/products/desurl:\d+)
 * 
 * @param int $desurl
 */
$app->get('/products/:desurl', function ($desurl) {

    $product = new Product();


    $product->getFromURL($desurl);

    $page = new Page();
    $page->setTpl("product-detail", [
        'product' => $product->getValues(),
        'categories' => $product->getCategories()
    ]);
});

/**
 * @route(/cart)
 */
$app->get('/cart', function () {

    $cart = Cart::getFromSession();

    $page = new Page();
    $page->setTpl("cart", [
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts(),
        'error' => Cart::getMsgError()
    ]);
});

/**
 * @route(/cart/idproduct:/add)
 * 
 * @param int $idproduct
 */
$app->get('/cart/:idproduct/add', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

    $cart = Cart::getFromSession();
    for ($i = 0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit;
});

/**
 * @route(/cart/idproduct:/minus)
 * 
 * @param int $idproduct
 */
$app->get('/cart/:idproduct/minus', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();
    $cart->removeProduct($product);

    header("Location: /cart");
    exit;
});

/**
 * @route(/cart/idproduct:/remove)
 * 
 * @param int $idproduct
 */
$app->get('/cart/:idproduct/remove', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();
    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;
});

/**
 * @route(/cart/freight, post)
 * 
 */
$app->post('/cart/freight', function () {

    $cart = Cart::getFromSession();
    $cart->setFreight($_POST['zipcode']);

    header("Location: /cart");
    exit;
});
