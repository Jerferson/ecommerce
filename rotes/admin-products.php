<?php

use Dale\Model\Product;
use Dale\Model\User;
use Dale\Page;
use Dale\PageAdmin;

/**
 * @route(/admin/products)
 */
$app->get('/admin/products', function () {

    User::verifyLogin();

    $products = Product::listAll();

    $page = new PageAdmin();
    $page->setTpl("products", [
        'products' => $products
    ]);
});

/**
 * @route(/admin/products/create)
 */
$app->get('/admin/products/create', function () {

    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl("products-create");
});

/**
 * @route(/admin/products/create, post)
 */
$app->post('/admin/products/create', function () {

    User::verifyLogin();

    $product = new Product();
    $product->setData($_POST);
    $product->save();

    header("Location: /admin/products");
    exit;
});

/**
 * @route(/admin/products/idproduct:/delete)
 * 
 * @param int $idproduct
 */
$app->get('/admin/products/:idproduct/delete', function ($idproduct) {

    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    $product->delete();

    header("Location: /admin/products");
    exit;
});

/**
 * @route(/admin/products/idproduct:)
 * 
 * @param int $idproduct
 */
$app->get('/admin/products/:idproduct', function ($idproduct) {

    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);

    $page = new PageAdmin();
    $page->setTpl("products-update", [
        'product' => $product->getValues()
    ]);
});

/**
 * @route(/admin/products/idproduct:\d+, post)
 * 
 * @param int $idproduct
 */
$app->post('/admin/products/:idproduct', function ($idproduct) {

    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    $product->setData($_POST);
    $product->save();

    header("Location: /admin/products");
    exit;
});

/**
 * @route(/products/idproduct:\d+)
 * 
 * @param int $idproduct
 */
$app->get('/products/:idproduct', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $page = new Page();
    $page->setTpl("product", [
        'product' => $product->getValues(),
        'products' => []
    ]);
});
