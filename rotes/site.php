<?php

use Dale\Model\Address;
use Dale\Model\Cart;
use Dale\Model\Category;
use Dale\Model\Product;
use Dale\Model\User;
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

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $category = new Category();
    $category->get((int)$idcategory);

    $pagination = $category->getProductsPage($page);

    $pages = [];

    for ($i = 1; $i <= $pagination['pages']; $i++) {
        array_push($pages, [
            'link' => '/categories/' . $category->getidcategory() . '?page=' . $i,
            'page' => $i
        ]);
    }

    $page = new Page();
    $page->setTpl("category", [
        'category' => $category->getValues(),
        'products' => $pagination['data'],
        'pages' => $pages
    ]);
});

/**
 * @route(/products/desurl:\d+)
 * 
 * @param int $desurl
 */
$app->get('/products/:desurl', function ($desurl) {

    $product = new Product();


    $product->getFromURL($desurl);

    $page = new Page();
    $page->setTpl("product-detail", [
        'product' => $product->getValues(),
        'categories' => $product->getCategories()
    ]);
});

/**
 * @route(/cart)
 */
$app->get('/cart', function () {

    $cart = Cart::getFromSession();

    $page = new Page();
    $page->setTpl("cart", [
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts(),
        'error' => Cart::getMsgError()
    ]);
});

/**
 * @route(/cart/idproduct:/add)
 * 
 * @param int $idproduct
 */
$app->get('/cart/:idproduct/add', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

    $cart = Cart::getFromSession();
    for ($i = 0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit;
});

/**
 * @route(/cart/idproduct:/minus)
 * 
 * @param int $idproduct
 */
$app->get('/cart/:idproduct/minus', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();
    $cart->removeProduct($product);

    header("Location: /cart");
    exit;
});

/**
 * @route(/cart/idproduct:/remove)
 * 
 * @param int $idproduct
 */
$app->get('/cart/:idproduct/remove', function ($idproduct) {

    $product = new Product();
    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();
    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;
});

/**
 * @route(/cart/freight, post)
 * 
 */
$app->post('/cart/freight', function () {

    $cart = Cart::getFromSession();
    $cart->setFreight($_POST['zipcode']);

    header("Location: /cart");
    exit;
});

/**
 * @route(/checkout)
 * 
 */
$app->get('/checkout', function () {

    User::verifyLogin(false);

    $cart = Cart::getFromSession();

    $address = new Address();

    $page = new Page();
    $page->setTpl('checkout', [
        'cart' => $cart->getValues(),
        'address' => $address->getValues()
    ]);
});

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
 * @route(/register, post)
 */
$app->post('/register', function () {

    $_SESSION['registerValues'] = $_POST;
    if (!isset($_POST['name']) || $_POST['name'] == '') {
        User::setErrorRegister("Preencha seu nome.");
        header("Location: /login");
        exit;
    }

    if (!isset($_POST['email']) || $_POST['email'] == '') {
        User::setErrorRegister("Preencha seu e-mail.");
        header("Location: /login");
        exit;
    }

    if (!isset($_POST['password']) || $_POST['password'] == '') {
        User::setErrorRegister("Preencha a senha.");
        header("Location: /login");
        exit;
    }

    if (User::checkLoginExist($_POST['email'])) {
        User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
        header("Location: /login");
        exit;
    }

    $user = new User();
    $user->setData([
        'inadmin' => 0,
        'deslogin' => $_POST['email'],
        'desperson' => $_POST['name'],
        'desemail' => $_POST['email'],
        'despassword' => $_POST['password'],
        'nrphone' => $_POST['phone']
    ]);

    $user->save();

    $_SESSION['registerValues'] = NULL;

    User::login($_POST['email'], $_POST['password']);
    header("Location: /checkout");
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
