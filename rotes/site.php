<?php

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
