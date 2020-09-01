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

        Category::updateFile();
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

        Category::updateFile();
    }

    /**
     * Função para atualizar a lista de categorias
     * 
     * @return void 
     */
    public static function updateFile()
    {
        $categories = Category::listAll();

        $html = [];

        foreach ($categories as $row) {
            array_push($html, '<li><a href="/categories/' . $row['idcategory'] . '">' . $row['descategory'] . '</a></li>');
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
    }
}
