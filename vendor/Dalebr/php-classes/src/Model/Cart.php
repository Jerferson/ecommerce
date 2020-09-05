<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Model;

class Cart extends Model
{

    /** @var SESSION Nome da sessão que guarda o carrinho de compras */
    const SESSION = "Cart";

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
}
