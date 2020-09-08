<?php

use Dale\Model\User;
use Dale\Page;

/**
 * @route(/login)
 * 
 */
$app->get('/login', function () {

    $page = new Page();
    $page->setTpl('login', [
        'error' => User::getError(),
        'errorRegister' => User::getErrorRegister(),
        'registerValues' => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name' => '', 'email' => '', 'phone' => '']
    ]);

    $_SESSION['registerValues'] = NULL;
});

/**
 * @route(/login, post)
 */
$app->post('/login', function () {

    try {

        User::login($_POST['login'], $_POST['password']);
    } catch (Exception $e) {

        User::setError($e->getMessage());
    }

    header("Location: /checkout");
    exit;
});

/**
 * @route(/logout)
 */
$app->get('/logout', function () {

    User::logout();
    header("Location: /login");
    exit;
});

/**
 * @route(/forgot)
 */
$app->get('/forgot', function () {

    $page = new Page();
    $page->setTpl("forgot");
});

/**
 * @route(/forgot, post)
 */
$app->post('/forgot', function () {

    $user = User::getForgot($_POST["email"], false);

    header("Location: /forgot/sent");
    exit;
});

/**
 * @route(/forgot/sent)
 */
$app->get('/forgot/sent', function () {
    $page = new Page();

    $page->setTpl("forgot-sent");
});

/**
 * @route(/forgot/reset)
 */
$app->get('/forgot/reset', function () {

    $user = User::validForgotDecrypt($_GET["code"]);
    $page = new Page();

    $page->setTpl("forgot-reset", array(
        "name" => $user["desperson"],
        "code" => $_GET["code"]
    ));
});

/**
 * @route(/forgot/reset, post)
 */
$app->post('/forgot/reset', function () {

    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUsed($forgot["idrecovery"]);

    $user = new User();
    $user->get((int)$forgot["iduser"]);

    $user->setPassword($_POST["password"]);

    $page = new Page();
    $page->setTpl("forgot-reset-success");
});
