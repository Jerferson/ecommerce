<?php

use Dale\Model\Category;
use Dale\Model\User;
use Dale\Page;
use Dale\PageAdmin;

/**
 * @route(/admin/categories)
 */
$app->get('/admin/categories', function () {

    User::verifyLogin();

    $categories = Category::listAll();

    $page = new PageAdmin();
    $page->setTpl("categories", [
        'categories' => $categories
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
        'products' => []
    ]);
});
