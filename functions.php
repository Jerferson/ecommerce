<?php

/**
 * Função para fazer o checkList dos produtos e carregar a imagem
 * 
 * @param float $vlprice
 * @return float 
 */
function formatPrice(float $vlprice)
{
    return number_format($vlprice, 2, ",", ".");
}
