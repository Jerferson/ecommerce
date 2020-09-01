<?php

use Dale\Page;

/**
 * @route(/)
 */
$app->get('/', function () {

    $page = new Page();

    $page->setTpl("index");
});
