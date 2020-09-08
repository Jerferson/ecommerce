<?php

use Dale\Model\Address;
use Dale\Model\Cart;
use Dale\Model\Category;
use Dale\Model\Order;
use Dale\Model\OrderStatus;
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
 * @route(/checkout)
 * 
 */
$app->get('/checkout', function () {

    User::verifyLogin(false);

    $address = new Address();

    $cart = Cart::getFromSession();

    if (isset($_GET['zipcode'])) {
        $_GET['zipcode'] = $cart->getdeszipcode();
    }

    if (isset($_GET['zipcode'])) {
        $address->loadFromCEP($_GET['zipcode']);

        $cart->setdeszipcode($_GET['zipcode']);
        $cart->seve();
        $cart->getCalculateToral();
    }

    if (!$address->getdesaddress()) $address->setdesaddress('');
    if (!$address->getdescomplement()) $address->setdescomplement('');
    if (!$address->getdesdistrict()) $address->setdesdistrict('');
    if (!$address->getdescity()) $address->setdescity('');
    if (!$address->getdesstate()) $address->setdesstate('');
    if (!$address->getdescountry()) $address->setdescountry('');
    if (!$address->getdeszipcode()) $address->setdeszipcode('');

    $page = new Page();
    $page->setTpl('checkout', [
        'cart' => $cart->getValues(),
        'address' => $address->getValues(),
        'products' => $cart->getProducts(),
        'error' => Address::getMsgError()
    ]);
});

/**
 * @route(/checkout, post)
 * 
 */
$app->post('/checkout', function () {

    User::verifyLogin(false);

    if (!isset($_POST['zipcode']) || $_POST['zipcode'] == '') {
        Address::setMsgError('Informe o CEP.');
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desaddress']) || $_POST['desaddress'] == '') {
        Address::setMsgError('Informe o endereço.');
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] == '') {
        Address::setMsgError('Informe o bairro.');
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['descity']) || $_POST['descity'] == '') {
        Address::setMsgError('Informe a cidade.');
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['desstate']) || $_POST['desstate'] == '') {
        Address::setMsgError('Informe o estado.');
        header("Location: /checkout");
        exit;
    }

    if (!isset($_POST['descountry']) || $_POST['descountry'] == '') {
        Address::setMsgError('Informe o país.');
        header("Location: /checkout");
        exit;
    }

    $user = User::getFromSession();

    $address = new Address();

    $_POST['deszipcode'] = $_POST['zipcode'];
    $_POST['idperson'] = $user->getidperson();

    $address->setData($_POST);
    $address->save();

    $cart = Cart::getFromSession();

    $totals = $cart->getCalculateToral();

    $order = new Order();

    $order->setData([
        'idcart' => $cart->getidcart(),
        'iduser' => $user->getiduser(),
        'idstatus' => OrderStatus::EM_ABERTO,
        'idaddress' => $address->getidaddress(),
        'vltotal' => $cart->getvltotal()
    ]);

    $order->save();

    header("Location: /order/" . $order->getidorder());
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
 * @route(/profile)
 */
$app->get('/profile', function () {

    User::verifyLogin(false);

    $user = User::getFromSession();

    $page = new Page();

    $page->setTpl("profile", array(
        "user" => $user->getValues(),
        "profileMsg" => User::getSuccess(),
        "profileError" => User::getError()
    ));
});

/**
 * @route(/profile, post)
 */
$app->post('/profile', function () {

    User::verifyLogin(false);

    if (!isset($_POST['desperson']) || $_POST['desperson'] == '') {
        User::setError('Preencha seu nome.');
        header("Location: /profile");
        exit;
    }

    if (!isset($_POST['desemail']) || $_POST['desemail'] == '') {
        User::setError('Preencha seu e-mail.');
        header("Location: /profile");
        exit;
    }
    $user = User::getFromSession();

    if ($_POST['desemail'] !== $user->getdesemail()) {
        if (User::checkLoginExist($_POST['desemail'])) {
            User::setError('Este e-mail já está cadastrado.');
            header("Location: /profile");
            exit;
        }
    }

    $page = new Page();

    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];

    $user->setData($_POST);
    $user->update();

    User::setSuccess('Dados alterados com sucesso.');

    $page->setTpl("profile", array(
        "user" => $user->getValues(),
        "profileMsg" => User::getSuccess(),
        "profileError" => User::getError()
    ));
});

/**
 * @route(/order)
 */
$app->get('/order/:idorder', function ($idorder) {

    User::verifyLogin(false);

    $order = new Order();

    $order->get((int)$idorder);

    $page = new Page();

    $page->setTpl("payment", array(
        "order" => $order->getValues()
    ));
});

/**
 * @route(/order)
 */
$app->get('/boleto/:idorder', function ($idorder) {
    User::verifyLogin(false);

    $order = new Order();
    $order->get((int)$idorder);

    // DADOS DO BOLETO PARA O SEU CLIENTE
    $dias_de_prazo_para_pagamento = 10;
    $taxa_boleto = 5.00;
    $data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
    // $valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
    // $valor_cobrado = str_replace(".", "", $valor_cobrado);
    // $valor_cobrado = str_replace(",", ".", $valor_cobrado);
    $valor_boleto = number_format($order->getvltotal() + $taxa_boleto, 2, ',', '');

    $dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
    $dadosboleto["numero_documento"] = $order->getidorder();    // Num do pedido ou nosso numero
    $dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
    $dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
    $dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
    $dadosboleto["valor_boleto"] = $valor_boleto;     // Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

    // DADOS DO SEU CLIENTE
    $dadosboleto["sacado"] = $order->getdesperson();
    $dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
    $dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " - CEP: " . $order->getdeszipcode();

    // INFORMACOES PARA O CLIENTE
    $dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Dale E-commerce";
    $dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
    $dadosboleto["demonstrativo3"] = "";
    $dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
    $dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
    $dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: dale.suport@gmail.com";
    $dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Loja Dale E-commerce - www.dalebagual.com.br";

    // DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
    $dadosboleto["quantidade"] = "";
    $dadosboleto["valor_unitario"] = "";
    $dadosboleto["aceite"] = "";
    $dadosboleto["especie"] = "R$";
    $dadosboleto["especie_doc"] = "";


    // ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


    // DADOS DA SUA CONTA - ITAÚ
    $dadosboleto["agencia"] = "0000"; // Num da agencia, sem digito
    $dadosboleto["conta"] = "00000";    // Num da conta, sem digito
    $dadosboleto["conta_dv"] = "8";     // Digito do Num da conta

    // DADOS PERSONALIZADOS - ITAÚ
    $dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

    // SEUS DADOS
    $dadosboleto["identificacao"] = "Fulano de Tal";
    $dadosboleto["cpf_cnpj"] = "000.000.000-00";
    $dadosboleto["endereco"] = "Rua Frei Eurico de Melo, 55 , 81250-615";
    $dadosboleto["cidade_uf"] = "Curitiba - PR";
    $dadosboleto["cedente"] = "DALE STORE";

    // NÃO ALTERAR!
    $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
    require_once($path . "funcoes_itau.php");
    require_once($path . "layout_itau.php");
});

/**
 * @route(/profile/orders)
 */
$app->get('/profile/orders', function () {

    User::verifyLogin(false);

    $user = User::getFromSession();

    $page = new Page();

    $page->setTpl("profile-orders", array(
        "orders" => $user->getOrders()
    ));
});

/**
 * @route(/profile/orders/:idorder)
 */
$app->get('/profile/orders/:idorder', function ($idorder) {

    User::verifyLogin(false);

    $order = new Order();
    $order->get((int)$idorder);

    $cart = new Cart();
    $cart->get((int)$order->getidcart());
    $cart->getCalculateToral();

    $page = new Page();

    $page->setTpl("profile-orders-detail", array(
        "order" => $order->getValues(),
        "cart" => $cart->getValues(),
        'products' => $cart->getProducts()
    ));
});
