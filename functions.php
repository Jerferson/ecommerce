<?php

use Dale\Model\Cart;
use Dale\Model\User;

/**
 * Função para fazer o checkList dos produtos e carregar a imagem
 * 
 * @param  $vlprice
 * @return float 
 */
function formatPrice($vlprice)
{
    if (!$vlprice) $vlprice = 0;
    return number_format($vlprice, 2, ",", ".");
}

/**
 * Função para verificar usuário está logado dentro do template
 * 
 * @param booleano $inadmin
 * @return booleano 
 */
function checkLogin($inadmin)
{
    return User::checkLogin($inadmin);
}

/**
 * Função para retornar o nome do usuário logado
 * 
 * @return string 
 */
function getUserName()
{
    $user = User::getFromSession();
    return $user->getdesperson();
}

/**
 * Função para retornar o número de itens dentro do carrinho
 * 
 * @return string 
 */
function getCartNrQtd()
{
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return $totals['nrqtd'];
}

/**
 * Função para retornar o Valor dos itens dentro do carrinho sem valor do frete
 * 
 * @return string 
 */
function getCartVlSubtotal()
{
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return formatPrice($totals['vlprice']);
}
