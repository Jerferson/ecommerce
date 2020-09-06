<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Model;

class Cart extends Model
{

    /** @var SESSION Nome da sessão que guarda o carrinho de compras */
    const SESSION = "Cart";
    /** @var SESSION_ERROR Nome da sessão que guarda os erros do carrinho de compras */
    const SESSION_ERROR = "CartError";

    /**
     * Função para retornar o carrinho de compras
     * 
     * @return Cart
     */
    public static function getFromSession()
    {
        $cart = new Cart();

        if (isset($_SESSION[Cart::SESSION]) && $_SESSION[Cart::SESSION]['idcart'] > 0) {

            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);

            return $cart;
        }

        $cart->getFromSessionID();

        if (!(int)$cart->getidcart() > 0) {

            $data = [
                'dessessionid' => session_id()
            ];

            if (User::checkLogin(false)) {
                $user = User::getFromSession();
                $data['iduser'] = $user->getiduser();
            }

            $cart->setData($data);
            $cart->save();
            $cart->serToSession();
        }
        return $cart;
    }

    /**
     * Função para setar a sessão e gravar o carrinho na sessão
     * 
     * @return void
     */
    public function serToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    /**
     * Função para carregar os dados do carrinho pelo session_id do carinho
     * 
     * @param int $idcart
     * @return void
     */
    public function getFromSessionID()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart;", [
            ':idcart' => session_id()
        ]);

        if ($results) {
            $this->setData($results[0]);
        }
    }

    /**
     * Função para retornar o carrinho pelo idcart
     * 
     * @param int $idcart
     * @return void
     */
    public function get(int $idcart)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart;", [
            ':idcart' => $idcart
        ]);

        if ($results) {
            $this->setData($results[0]);
        }
    }

    /**
     * Função salvar carrinho
     * 
     * @return void 
     */
    public function save()
    {
        $sql = new Sql();

        $results =  $sql->select(
            "CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays);",
            array(
                ":idcart" => $this->getidcart(),
                ":dessessionid" => $this->getdessessionid(),
                ":iduser" => $this->getiduser(),
                ":deszipcode" => $this->getdeszipcode(),
                ":vlfreight" => $this->getvlfreight(),
                ":nrdays" => $this->getnrdays()
            )
        );
        $this->setData($results[0]);
    }

    /**
     * Função para adicionar o produto n carrinho
     * 
     * @return void 
     */
    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $results =  $sql->query(
            "INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct);",
            array(
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            )
        );

        $this->getCalculateToral();
    }

    /**
     * Função para remover o produto do carrinho. Por default é removido um item por vez
     * 
     * @param Product $product
     * @param booleano $all
     * @return void 
     */
    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();

        $limit = ' LIMIT 1';
        if ($all) {
            $limit = '';
        }

        $sql->query(
            "UPDATE tb_cartsproducts SET dtremoved = NOW() 
                WHERE idcart = :idcart 
                    AND idproduct = :idproduct 
                    AND dtremoved IS NULL {$limit};",
            array(
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            )
        );

        $this->getCalculateToral();
    }

    /**
     * Função resgatar todos os produtos do banco de dados.
     * 
     * @return Product[] 
     */
    public function getProducts()
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT p.idproduct, p.desproduct, p.vlprice, p.vlwidth, p.vlheight, p.vllength, p.vlweight, p.desurl,  COUNT(*) AS nrqtd, SUM(p.vlprice) AS vltotal
                FROM tb_cartsproducts cp
                INNER JOIN tb_products p ON cp.idproduct = p.idproduct 
                WHERE cp.idcart = :idcart 
                    AND dtremoved IS NULL 
                    GROUP BY p.idproduct, p.desproduct, p.vlprice, p.vlwidth, p.vlheight, p.vllength, p.vlweight, p.desurl
                    ORDER BY p.desproduct;",
            array(
                ":idcart" => $this->getidcart()
            )
        );

        return  Product::checkList($results);
    }

    /**
     * Função resgatar os  totais dos produtos do banco de dado.
     * 
     * @return Product[] 
     */
    public function getProductsTotals()
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT SUM(vlprice) AS vlprice,
                    SUM(vlwidth) AS vlwidth,
                    SUM(vlheight) AS vlheight,
                    SUM(vllength) AS vllength,
                    SUM(vlweight) AS vlweight,
                    COUNT(*) AS nrqtd
            FROM tb_products p
            INNER JOIN tb_cartsproducts cp ON p.idproduct = cp.idproduct 
            WHERE cp.idcart = :idcart AND dtremoved IS NULL;",
            array(
                ":idcart" => $this->getidcart()
            )
        );

        if (count($results) > 0) {
            return $results[0];
        }

        return  [];
    }

    /**
     * Função resgatar os  totais dos produtos do banco de dados e fazer a consulta nos correios
     * 
     * @param string $nrzipcode
     * @return Product[] 
     */
    public function setFreight($nrzipcode)
    {
        $nrzipcode = str_replace('-', '', $nrzipcode);

        $totals = $this->getProductsTotals();

        if ($totals['nrqtd'] > 0) {

            if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
            if ($totals['vllength'] < 16) $totals['vllength'] = 16;

            $qs = http_build_query([
                'nCdEmpresa' => '',
                'sDsSenha' => '',
                'nCdServico' => '40010',
                'sCepOrigem' => '89680000',
                'sCepDestino' => $nrzipcode,
                'nVlPeso' => $totals['vlweight'],
                'nCdFormato' => '1',
                'nVlComprimento' => $totals['vllength'],
                'nVlAltura' => $totals['vlheight'],
                'nVlLargura' => $totals['vlwidth'],
                'nVlDiametro' => '0',
                'sCdMaoPropria' => 'S',
                'nVlValorDeclarado' => $totals['vlprice'],
                'sCdAvisoRecebimento' => 'S',
            ]);

            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" . $qs);

            $results = $xml->Servicos->cServico;

            Cart::clearMsgError();
            if ($results->MsgErro != '') {

                Cart::setMsgError((string)$results->MsgErro);
            }

            $this->setnrdays($results->PrazoEntrega);
            $this->setvlfreight(Cart::formatValueToDecimal($results->Valor));
            $this->setdeszipcode($nrzipcode);
            $this->save();

            return $results;
        }
    }

    /**
     * Função para converter valor para decimal
     * 
     * @param string $value
     * @return float 
     */
    public static function formatValueToDecimal($value): float
    {
        $value = str_replace('.', '', $value);
        return str_replace(',', '.', $value);
    }

    /**
     * Função para armazenar os erros na sessão
     * 
     * @param string $msg
     */
    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    /**
     * Função para resgatar os erros na sessão
     * 
     * @return string
     */
    public static function getMsgError()
    {
        $msg =  (isset($_SESSION[Cart::SESSION_ERROR])) ?  $_SESSION[Cart::SESSION_ERROR] : '';

        Cart::clearMsgError();

        return $msg;
    }

    /**
     * Função para limpar os erros da sessão
     */
    public static function clearMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }

    /**
     * Função recalcuylar o frete quando existe alteração na lista de produtos
     */
    public function updateFreight()
    {
        if ($this->getdeszipcode() != '') {
            $this->setFreight($this->getdeszipcode());
        }
    }

    /**
     * Função que sobrescreve a função que é herdada
     * 
     * @return array
     */
    public function getValues()
    {
        $this->getCalculateToral();
        return parent::getValues();
    }

    /**
     * Função que calcula os valores subtotal e total do carrinho
     */
    public function getCalculateToral()
    {
        $this->updateFreight();
        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }
}
