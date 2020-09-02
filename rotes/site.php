<?php

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

    $category = new Category();
    $category->get((int)$idcategory);

    $page = new Page();
    $page->setTpl("category", [
        'category' => $category->getValues(),
        'products' => Product::checkList($category->getProducts()),
    ]);
});
