<?php

use Dale\Model\User;
use Dale\PageAdmin;

/**
 * @route(/admin/users)
 */
$app->get('/admin/users', function () {

    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $pagination = User::getPage($search, $page);

    $pages = [];

    for ($x = 0; $x < $pagination['pages']; $x++) {
        array_push($pages, [
            'href' => '/admin/users?' . http_build_query([
                'page' => $x + 1,
                'search' => $search
            ]),
            'text' => $x + 1
        ]);
    }

    $page = new PageAdmin();
    $page->setTpl("users", array(
        "users" => $pagination['data'],
        "search" => $search,
        "pages" => $pages
    ));
});

/**
 * @route(/admin/users/create)
 */
$app->get('/admin/users/create', function () {

    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl("users-create");
});

/**
 * @route(/admin/users/iduser:/delete)
 * 
 * @param int $iduser
 */
$app->get('/admin/users/:iduser/delete', function ($iduser) {
    User::verifyLogin();

    $user = new User();
    $user->get((int)$iduser);

    $user->delete();
    header("Location: /admin/users");
    exit;
});

/**
 * @route(/admin/users/iduser:\d+)
 * 
 * @param int $iduser
 */
$app->get('/admin/users/:iduser', function ($iduser) {

    User::verifyLogin();

    $user = new User();
    $user->get((int)$iduser);

    $page = new PageAdmin();
    $page->setTpl("users-update", array(
        "user" => $user->getValues()
    ));
});

/**
 * @route(/admin/users/create, post)
 */
$app->post('/admin/users/create', function () {

    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->setData($_POST);
    $user->save();

    header("Location: /admin/users");
    exit;
});

/**
 * @route(/admin/users/iduser:\d+, post)
 * 
 * @param int $iduser
 */
$app->post('/admin/users/:iduser', function ($iduser) {
    User::verifyLogin();

    $user = new User();
    $user->get((int)$iduser);

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->setData($_POST);
    $user->update();
    header("Location: /admin/users");
    exit;
});
