<?php

use Dale\Model\User;
use Dale\PageAdmin;

/**
 * @route(/admin)
 */
$app->get('/admin', function () {

    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("index");
});

/**
 * @route(/admin/login)
 */
$app->get('/admin/login', function () {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("login");
});

/**
 * @route(/admin/login, post)
 */
$app->post('/admin/login', function () {

    User::login($_POST["login"], $_POST["password"]);

    header("Location: /admin");
    exit;
});

/**
 * @route(/admin/logout)
 */
$app->get('/admin/logout', function () {

    User::logout();

    header("Location: /admin/login");
    exit;
});

/**
 * @route(/admin/forgot)
 */
$app->get('/admin/forgot', function () {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("forgot");
});

/**
 * @route(/admin/forgot, post)
 */
$app->post('/admin/forgot', function () {

    User::getForgot($_POST["email"]);

    header("Location: /admin/forgot/sent");
    exit;
});

/**
 * @route(/admin/forgot/sent)
 */
$app->get('/admin/forgot/sent', function () {
    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-sent");
});

/**
 * @route(/admin/forgot/reset)
 */
$app->get('/admin/forgot/reset', function () {

    $user = User::validForgotDecrypt($_GET["code"]);
    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset", array(
        "name" => $user["desperson"],
        "code" => $_GET["code"]
    ));
});

/**
 * @route(/admin/forgot/reset, post)
 */
$app->post('/admin/forgot/reset', function () {

    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUsed($forgot["idrecovery"]);

    $user = new User();
    $user->get((int)$forgot["iduser"]);

    $user->setPassword($_POST["password"]);

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("forgot-reset-success");
});
