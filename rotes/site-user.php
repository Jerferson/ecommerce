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

/**
 * @route(/profile/change-password)
 */
$app->get('/profile/change-password', function () {

    User::verifyLogin(false);
    $page = new Page();

    $page->setTpl("profile-change-password", [
        'changePassError' => User::getError(),
        'changePassSuccess' => User::getSuccess()
    ]);
});

/**
 * @route(/profile/change-password, post)
 */
$app->post('/profile/change-password', function () {

    User::verifyLogin(false);

    // Verifica se o usuário digitou a senha
    if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {
        User::setError("Digite a sua senha atual.");
        header("Location: /profile/change-password");
        exit;
    }

    // Verifica se o usuário digitou a nova senha
    if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {
        User::setError("Digite a nova senha.");
        header("Location: /profile/change-password");
        exit;
    }

    // Verifica se o usuário digitou a nova senha e a confirmação iguais
    if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {
        User::setError("Confirme a nova senha.");
        header("Location: /profile/change-password");
        exit;
    }

    // Verifica se o usuário digitou a nova senha e a confirmação iguais
    if ($_POST['new_pass'] !== $_POST['new_pass-confirm']) {
        User::setError("Sua nova senha e a confirmação devem ser iguais.");
        header("Location: /profile/change-password");
        exit;
    }

    $user = User::getFromSession();

    // Verifica se o usuário digitou a senha é válida
    if (!password_verify($_POST['current_pass'], $user->getdespassword())) {
        User::setError("A senha está inválida.");
        header("Location: /profile/change-password");
        exit;
    }

    // Verifica se o usuário digitou a senha igual a senha anterior
    if ($_POST['current_pass'] === $_POST['new_pass']) {
        User::setError("Sua nova senha deve ser diferente da atual.");
        header("Location: /profile/change-password");
        exit;
    }

    $user->setdespassword($_POST['new_pass']);
    $user->update();

    User::setSuccess("Senha alterada com sucesso.");
    header("Location: /profile/change-password");
    exit;
});
