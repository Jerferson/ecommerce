<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Model;

class Address extends Model
{

    const ERROR = 'AddressError';

    /**
     * Função responsável consultar os dados no serviço do ViaCEP e resgatar os dados do endereço
     * 
     * @param string $nrcep
     * @return object
     */
    public static function getCEP($nrcep)
    {
        $nrcep = str_replace('-', '', $nrcep);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "viacep.com.br/ws/$nrcep/json/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $data;
    }

    /**
     * Função responsável por carregar os dados do cep no objeto local
     * 
     * @param string $nrcep
     */
    public function loadFromCEP($nrcep)
    {
        $data = Address::getCEP($nrcep);

        if (isset($data['logradouro']) && $data['logradouro']) {

            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry('Brasil');
            $this->setdeszipcode($nrcep);
        }
    }

    /**
     * Função responsável por carregar os dados do cep no objeto local
     * 
     * @param string $nrcep
     */
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [
            ':idaddress' => $this->getidaddress(),
            ':idperson' => $this->getidperson(),
            ':desaddress' => utf8_decode($this->getdesaddress()),
            ':descomplement' => utf8_decode($this->getdescomplement()),
            ':descity' => utf8_decode($this->getdescity()),
            ':desstate' => utf8_decode($this->getdesstate()),
            ':descountry' => utf8_decode($this->getdescountry()),
            ':deszipcode' => $this->getdeszipcode(),
            ':desdistrict' => utf8_decode($this->getdesdistrict()),
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    /**
     * Função para armazenar os erros na sessão
     * 
     * @param string $msg
     */
    public static function setMsgError($msg)
    {
        $_SESSION[Address::ERROR] = $msg;
    }

    /**
     * Função para resgatar os erros na sessão
     * 
     * @return string
     */
    public static function getMsgError()
    {
        $msg =  (isset($_SESSION[Address::ERROR])) ?  $_SESSION[Address::ERROR] : '';

        Address::clearMsgError();

        return $msg;
    }

    /**
     * Função para limpar os erros da sessão
     */
    public static function clearMsgError()
    {
        $_SESSION[Address::ERROR] = NULL;
    }
}
