<?php

use Dale\Model\Category;
use Dale\Model\Product;
use Dale\Model\User;
use Dale\Page;
use Dale\PageAdmin;

/**
 * @route(/admin/categories)
 */
$app->get('/admin/categories', function () {

    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $pagination = Category::getPage($search, $page);

    $pages = [];

    for ($x = 0; $x < $pagination['pages']; $x++) {
        array_push($pages, [
            'href' => '/admin/categories?' . http_build_query([
                'page' => $x + 1,
                'search' => $search
            ]),
            'text' => $x + 1
        ]);
    }

    $page = new PageAdmin();
    $page->setTpl("categories", [
        'categories' => $pagination['data'],
        "search" => $search,
        "pages" => $pages
    ]);
});

/**
 * @route(/admin/categories/create)
 */
$app->get('/admin/categories/create', function () {

    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl("categories-create");
});

/**
 * @route(/admin/categories/create, post)
 */
$app->post('/admin/categories/create', function () {

    User::verifyLogin();

    $category = new Category();
    $category->setData($_POST);
    $category->save();

    header("Location: /admin/categories");
    exit;
});

/**
 * @route(/admin/categories/idcategory:/delete)
 * 
 * @param int $idcategory
 */
$app->get('/admin/categories/:idcategory/delete', function ($idcategory) {

    User::verifyLogin();

    $category = new Category();
    $category->get((int)$idcategory);
    $category->delete();

    header("Location: /admin/categories");
    exit;
});

/**
 * @route(/admin/categories/idcategory:)
 * 
 * @param int $idcategory
 */
$app->get('/admin/categories/:idcategory', function ($idcategory) {

    User::verifyLogin();

    $category = new Category();
    $category->get((int)$idcategory);

    $page = new PageAdmin();
    $page->setTpl("categories-update", [
        'category' => $category->getValues()
    ]);
});

/**
 * @route(/admin/categories/idcategory:\d+, post)
 * 
 * @param int $idcategory
 */
$app->post('/admin/categories/:idcategory', function ($idcategory) {

    User::verifyLogin();

    $category = new Category();
    $category->get((int)$idcategory);
    $category->setData($_POST);
    $category->save();

    header("Location: /admin/categories");
    exit;
});

/**
 * @route(/admin/categories/idcategory:/products)
 * 
 * @param int $idcategory
 */
$app->get('/admin/categories/:idcategory/products', function ($idcategory) {

    User::verifyLogin();

    $category = new Category();
    $category->get((int)$idcategory);

    $page = new PageAdmin();
    $page->setTpl("categories-products", [
        'category' => $category->getValues(),
        'productsRelated' => $category->getProducts(),
        'productsNotRelated' => $category->getProducts(false)
    ]);
});

/**
 * @route(/admin/categories/idcategory:/products/:idproduct/add)
 * 
 * @param int $idcategory
 */
$app->get('/admin/categories/:idcategory/products/:idproduct/add', function ($idcategory, $idproduct) {

    User::verifyLogin();

    $category = new Category();
    $category->get((int)$idcategory);

    $product = new Product();

    $product->get((int)$idproduct);

    $category->addProduct($product);

    header("Location: /admin/categories/" . $idcategory . "/products");
    exit;
});

/**
 * @route(/admin/categories/idcategory:/products/:idproduct/remove)
 * 
 * @param int $idcategory
 * @param int $idproduct
 */
$app->get('/admin/categories/:idcategory/products/:idproduct/remove', function ($idcategory, $idproduct) {

    User::verifyLogin();

    $category = new Category();
    $category->get((int)$idcategory);

    $product = new Product();

    $product->get((int)$idproduct);

    $category->removeProduct($product);

    header("Location: /admin/categories/" . $idcategory . "/products");
    exit;
});
