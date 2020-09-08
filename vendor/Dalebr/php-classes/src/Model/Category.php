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

        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory;");
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
            "CALL sp_categories_save(:idcategory, :descategory);",
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
            "SELECT * FROM tb_categories WHERE idcategory = :idcategory;",
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
            "DELETE FROM tb_categories WHERE idcategory = :idcategory;",
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

    /**
     * Função para retornar todos os produtos que est~]ao ou não relacionados a categoria
     * 
     * @param boolean $related
     * @return array  
     */
    public function getProducts($related = true)
    {
        $sql = new Sql();

        if ($related) {

            return $sql->select(
                "SELECT * FROM tb_products where idproduct IN(
                SELECT p.idproduct
                FROM tb_products p
                INNER JOIN tb_productscategories pc ON p.idproduct = pc.idproduct
                WHERE pc.idcategory = :idcategory);",
                array(
                    ":idcategory" =>  $this->getidcategory()
                )
            );
        }

        return $sql->select(
            "SELECT * FROM tb_products where idproduct NOT IN(
                SELECT p.idproduct
                FROM tb_products p
                INNER JOIN tb_productscategories pc ON p.idproduct = pc.idproduct
                WHERE pc.idcategory = :idcategory);",
            array(
                ":idcategory" =>  $this->getidcategory()
            )
        );
    }

    /**
     * Função para retornar todos os produtos que est~]ao ou não relacionados a categoria
     * 
     * @param int $product
     * @return array  
     */
    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query(
            "INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct);",
            array(
                ':idcategory' => $this->getidcategory(),
                ':idproduct' => $product->getidproduct()
            )
        );
    }

    /**
     * Função para retornar produtos com paginação
     * 
     * @param int $page
     * @param int $itemsPerPage
     * @return array  
     */
    public function getProductsPage($page = 1, $itemsPerPage = 3)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
                FROM tb_products p
                INNER JOIN tb_productscategories pc ON p.idproduct = pc.idproduct
                inner join tb_categories c ON c.idcategory = pc.idcategory 
                WHERE pc.idcategory = :idcategory
                LIMIT $start, $itemsPerPage;",
            array(
                ':idcategory' => $this->getidcategory()
            )
        );

        $resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            'data' => Product::checkList($results),
            'total' => (int)$resultsTotal[0]['nrtotal'],
            'pages' => ceil((int)$resultsTotal[0]['nrtotal'] / $itemsPerPage)
        ];
    }

    /**
     * Função para retornar todos os produtos que est~]ao ou não relacionados a categoria
     * 
     * @param int $product
     * @return array  
     */
    public function removeProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query(
            "DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct;",
            array(
                ':idcategory' => $this->getidcategory(),
                ':idproduct' => $product->getidproduct()
            )
        );
    }

    /**
     * Função para retornar categorias com paginação
     * 
     * @param string $search
     * @param int $page
     * @param int $itemsPerPage
     * @return array  
     */
    public static function getPage($search = '', $page = 1, $itemsPerPage = 2)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();
        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS * FROM tb_categories c
                WHERE c.descategory LIKE '%$search%'
                ORDER BY c.descategory
                LIMIT $start, $itemsPerPage;"
        );

        $resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            'data' => $results,
            'total' => (int)$resultsTotal[0]['nrtotal'],
            'pages' => ceil((int)$resultsTotal[0]['nrtotal'] / $itemsPerPage)
        ];
    }
}
