<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Model;

class Category extends Model
{

    /**
     * Função para listar todas as categorias
     * 
     * @return array
     */
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    /**
     * Função cadastrar/edita categoria
     * 
     * @return void 
     */
    public function save()
    {
        $sql = new Sql();

        $results =  $sql->select(
            "CALL sp_categories_save(:idcategory, :descategory)",
            array(
                ":idcategory" => $this->getidcategory(),
                ":descategory" => $this->getdescategory()
            )
        );
        $this->setData($results[0]);
    }

    /**
     * Função para buscar categoria pelo ID
     * 
     * @param Int $idcategory 
     * @return void 
     */
    public function get($idcategory)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_categories WHERE idcategory = :idcategory",
            array(
                ":idcategory" => $idcategory
            )
        );

        $this->setData($results[0]);
    }

    /**
     * Função para excluir categoria
     * 
     * @return void 
     */
    public function delete()
    {
        $sql = new Sql();

        $results = $sql->query(
            "DELETE FROM tb_categories WHERE idcategory = :idcategory",
            array(
                ":idcategory" =>  $this->getidcategory()
            )
        );
    }
}
