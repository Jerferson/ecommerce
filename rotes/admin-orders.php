<?php

use Dale\Model\Order;
use Dale\Model\OrderStatus;
use Dale\Model\User;
use Dale\PageAdmin;

/**
 * @route(/admin/orders/:idorder/status)
 */
$app->get('/admin/orders/:idorder/status', function ($idorder) {

    User::verifyLogin();

    $order = new Order();
    $order->get((int)$idorder);


    $page = new PageAdmin();
    $page->setTpl("order-status", [
        "order" => $order->getValues(),
        "msgSuccess" => Order::getSuccess(),
        "msgError" => Order::getError(),
        "status" => OrderStatus::listAll()
    ]);
});

/**
 * @route(/admin/orders/:idorder/status, post)
 */
$app->post('/admin/orders/:idorder/status', function ($idorder) {

    User::verifyLogin();

    if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
        Order::setError('Informe o status atual.');
        header("Location: /admin/orders/" . $idorder . "/status");
        exit;
    }

    $order = new Order();
    $order->get((int)$idorder);
    $order->setidstatus($_POST['idstatus']);
    $order->save();
    Order::setSuccess("Status atualizado.");
    header("Location: /admin/orders/" . $idorder . "/status");
    exit;
});

/**
 * @route(/admin/orders/:idorder/delete)
 */
$app->get('/admin/orders/:idorder/delete', function ($idorder) {

    User::verifyLogin();

    $order = new Order();
    $order->get((int)$idorder);
    $order->delete();

    header("Location: /admin/orders");
    exit;
});

/**
 * @route(/admin/orders/:idorder)
 */
$app->get('/admin/orders/:idorder', function ($idorder) {

    User::verifyLogin();

    $order = new Order();
    $order->get((int)$idorder);

    $cart = $order->getCart();

    $page = new PageAdmin();
    $page->setTpl("order", [
        "order" => $order->getValues(),
        "cart" => $cart->getValues(),
        "products" => $cart->getProducts()
    ]);
});

/**
 * @route(/admin/orders)
 */
$app->get('/admin/orders', function () {

    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $pagination = Order::getPage($search, $page);

    $pages = [];

    for ($x = 0; $x < $pagination['pages']; $x++) {
        array_push($pages, [
            'href' => '/admin/orders?' . http_build_query([
                'page' => $x + 1,
                'search' => $search
            ]),
            'text' => $x + 1
        ]);
    }

    $page = new PageAdmin();
    $page->setTpl("orders", [
        'orders' => $pagination['data'],
        "search" => $search,
        "pages" => $pages
    ]);
});
