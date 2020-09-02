<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Model;

class Product extends Model
{

    /**
     * Função para listar todas as produtos
     * 
     * @return array
     */
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    /**
     * Função para fazer o checkList dos produtos e carregar a imagem
     * 
     * @param array $list
     * @return array 
     */
    public static function checkList($list)
    {
        foreach ($list as &$row) {

            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();
        }

        return $list;
    }

    /**
     * Função cadastrar/edita produto
     * 
     * @return void 
     */
    public function save()
    {
        $sql = new Sql();

        $results =  $sql->select(
            "CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)",
            array(
                ":idproduct" => $this->getidproduct(),
                ":desproduct" => $this->getdesproduct(),
                ":vlprice" => $this->getvlprice(),
                ":vlwidth" => $this->getvlwidth(),
                ":vlheight" => $this->getvlheight(),
                ":vllength" => $this->getvllength(),
                ":vlweight" => $this->getvlweight(),
                ":desurl" => $this->getdesurl()
            )
        );
        $this->setData($results[0]);
    }

    /**
     * Função busca produto pelo ID
     * 
     * @param Int $idproduct 
     * @return void 
     */
    public function get($idproduct)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_products WHERE idproduct = :idproduct",
            array(
                ":idproduct" => $idproduct
            )
        );

        $this->setData($results[0]);
    }

    /**
     * Função para excluir produto
     * 
     * @return void 
     */
    public function delete()
    {
        $sql = new Sql();

        $results = $sql->query(
            "DELETE FROM tb_products WHERE idproduct = :idproduct",
            array(
                ":idproduct" =>  $this->getidproduct()
            )
        );
    }

    /**
     * Função retornar o caminho da imagem
     * 
     * @return void
     */
    public function checkPhoto()
    {

        $url = "/res/site/img/product.jpg";

        if (file_exists(
            $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
                "res" . DIRECTORY_SEPARATOR .
                "site" . DIRECTORY_SEPARATOR .
                "img" . DIRECTORY_SEPARATOR .
                "products" . DIRECTORY_SEPARATOR .
                $this->getidproduct() . ".jpg"
        )) {
            $url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
        }

        $this->setdesphoto($url);
    }

    /**
     * Função sobrescreve getValues para retornar valores
     * 
     * @return object
     */
    public function getValues()
    {
        $this->checkPhoto();

        $values = parent::getValues();

        return $values;
    }

    /**
     * Função para salvar foto
     * 
     * @return void
     */
    public function setPhoto($file)
    {
        $extension = explode('.', $file['name']);
        $extension = end($extension);

        switch ($extension) {
            case "jpg":
            case "jpeg":
                $image = imagecreatefromjpeg($file["tmp_name"]);
                break;

            case "gif":
                $image = imagecreatefromgif($file["tmp_name"]);
                break;

            case "png":
                $image = imagecreatefrompng($file["tmp_name"]);
                break;
        }

        $dist =  $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
            "res" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg";

        imagejpeg($image, $dist);

        imagedestroy($image);

        $this->checkPhoto();
    }
}
