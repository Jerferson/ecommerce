<?php

session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;

$app = new Slim();

$app->config('debug', true);

require_once("functions.php");
require_once("rotes/site.php");
require_once("rotes/admin.php");
require_once("rotes/admin-users.php");
require_once("rotes/admin-categories.php");
require_once("rotes/admin-products.php");

$app->run();
