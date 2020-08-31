<?php

session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;
use \Dale\Page;
use \Dale\PageAdmin;
use \Dale\Model\User;

$app = new Slim();

$app->config('debug', true);

/**
 * @route(/)
 */
$app->get('/', function () {

    $page = new Page();

    $page->setTpl("index");
});

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
 * @route(/admin/users)
 */
$app->get('/admin/users', function () {

    User::verifyLogin();

    $users = User::listAll();

    $page = new PageAdmin();
    $page->setTpl("users", array(
        "users" => $users
    ));

    $page->setTpl("users");
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

    $user = User::getForgot($_POST["email"]);

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

    $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
        "cost" => 12
    ]);
    $user->setPassword($password);

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("forgot-reset-success");
});

$app->run();
