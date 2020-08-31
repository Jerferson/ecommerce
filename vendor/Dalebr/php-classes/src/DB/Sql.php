<?php

namespace Dale\DB;

class Sql
{

    private $conn;

    public function __construct()
    {

        $this->conn = new \PDO(
            "mysql:dbname=" . getenv('DALE_DB_NAME') . ";host=" . getenv('DALE_DB_HOST'),
            getenv('DALE_DB_USER'),
            getenv('DALE_DB_PASSWOARD')
        );
    }

    private function setParams($statement, $parameters = array())
    {

        foreach ($parameters as $key => $value) {

            $this->bindParam($statement, $key, $value);
        }
    }

    private function bindParam($statement, $key, $value)
    {

        $statement->bindParam($key, $value);
    }

    public function query($rawQuery, $params = array())
    {

        $stmt = $this->conn->prepare($rawQuery);

        $this->setParams($stmt, $params);

        $stmt->execute();
    }

    public function select($rawQuery, $params = array()): array
    {

        $stmt = $this->conn->prepare($rawQuery);

        $this->setParams($stmt, $params);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
