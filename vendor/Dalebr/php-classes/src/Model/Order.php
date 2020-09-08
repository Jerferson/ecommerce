<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Model;

class Order extends Model
{
    const SUCCESS = "Order-success";
    const ERROR = "Order-error";

    /**
     * Função responsável por salvar os dados do pedido
     * 
     * @param string $nrcep
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
            ':idorder' => $this->getidorder(),
            ':idcart' => $this->getidcart(),
            ':iduser' => $this->getiduser(),
            ':idstatus' => $this->getidstatus(),
            ':idaddress' => $this->getidaddress(),
            ':vltotal' => $this->getvltotal()
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    /**
     * Função responsável por carregar os dados do pedido do banco de dados
     * 
     * @param string $idorder
     */
    public function get($idorder)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * 
            FROM tb_orders o
            INNER JOIN tb_ordersstatus os USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users u ON u.iduser = o.iduser
            INNER JOIN tb_addresses a USING(idaddress)
            INNER JOIN tb_persons p ON p.idperson = u.idperson
            WHERE o.idorder = :idorder
        ", [
            ':idorder' => $idorder
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    /**
     * Função responsável listar todos os pedidos da base de dados
     * 
     * @return array
     */
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * 
            FROM tb_orders o
            INNER JOIN tb_ordersstatus os USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users u ON u.iduser = o.iduser
            INNER JOIN tb_addresses a USING(idaddress)
            INNER JOIN tb_persons p ON p.idperson = u.idperson
            ORDER BY o.dtregister DESC
        ");
    }

    /**
     * Função para deletar pedido
     * 
     */
    public function delete()
    {
        $sql = new Sql();

        $sql->select("DELETE FROM tb_orders 
             WHERE idorder = :idorder
        ", [
            ':idorder' => $this->getidorder()
        ]);
    }

    /**
     * Função para pegar o carrinho do pedido
     * 
     * @return object Cart
     */
    public function getCart()
    {
        $cart = new Cart();
        $cart->get((int)$this->getidcart());

        return $cart;
    }


    /**
     * Função para armazenar os erros de registro na sessão
     * 
     * @param string $message
     */
    public static function setSuccess($message)
    {
        $_SESSION[Order::SUCCESS] = $message;
    }

    /**
     * Função para resgatar os erros de registro na sessão
     * 
     * @return string
     */
    public static function getSuccess()
    {
        $message =  (isset($_SESSION[Order::SUCCESS])) ?  $_SESSION[Order::SUCCESS] : '';

        Order::clearSuccess();

        return $message;
    }

    /**
     * Função para limpar os erros de gegistro da sessão
     */
    public static function clearSuccess()
    {
        $_SESSION[Order::SUCCESS] = NULL;
    }

    /**
     * Função para armazenar os erros na sessão
     * 
     * @param string $errorMessage
     */
    public static function setError($errorMessage)
    {
        $_SESSION[Order::ERROR] = $errorMessage;
    }

    /**
     * Função para resgatar os erros na sessão
     * 
     * @return string
     */
    public static function getError()
    {
        $errorMessage =  (isset($_SESSION[Order::ERROR])) ?  $_SESSION[Order::ERROR] : '';

        Order::clearError();

        return $errorMessage;
    }

    /**
     * Função para limpar os erros da sessão
     */
    public static function clearError()
    {
        $_SESSION[Order::ERROR] = NULL;
    }
}
