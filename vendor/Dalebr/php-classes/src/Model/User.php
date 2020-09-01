<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Mailer;
use \Dale\Model;

class User extends Model
{
    const SESSION = "User";
    const SESS_CIPHER = 'BF-ECB';

    /**
     * Função para listar todos os usuários
     * 
     * @param string $login
     * @param string $password
     * @return object User
     */
    public static function login($login, $password)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_users WHERE deslogin = :LOGIN AND deleted = 0",
            array(
                ":LOGIN" => $login
            )
        );

        if (count($results) === 0) {
            throw new \Exception();
        }

        $data = $results[0];

        if (!password_verify($password, $data["despassword"])) {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }
        $user = new User();

        $user->setData($data);

        $_SESSION[User::SESSION] = $user->getValues();

        return $user;
    }

    /**
     * Função para verificar se o usuário está logado
     * 
     * @param boolean $inadmin
     * @return void 
     */
    public static function verifyLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
        ) {
            header("Location: /admin/login");
            exit;
        }
    }

    /**
     * Função para deslogar usuário do sistema
     * 
     * @return void 
     */
    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    /**
     * Função para listar todos os usuários
     * 
     * @return array
     */
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons p USING(idperson) WHERE a.deleted = 0 ORDER BY p.desperson");
    }

    /**
     * Função cadastrar novo usuário
     * 
     * @return void 
     */
    public function save()
    {
        $sql = new Sql();

        $results =  $sql->select(
            "CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":desperson" => $this->getdesperson(),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => password_hash($this->getdespassword(), PASSWORD_DEFAULT, [
                    "cost" => 12
                ]),
                ":desemail" => $this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            )
        );
        $this->setData($results[0]);
    }

    /**
     * Função para buscar usuário pelo ID
     * 
     * @param Int $iduser 
     * @return void 
     */
    public function get($iduser)
    {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_users u INNER JOIN tb_persons p USING(idperson) WHERE u.iduser = :iduser AND u.deleted = 0",
            array(
                ":iduser" => $iduser
            )
        );

        $this->setData($results[0]);
    }

    /**
     * Função para atualizar usuário
     * 
     * @return void 
     */
    public function update()
    {
        $sql = new Sql();

        $results =  $sql->select(
            "CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":iduser" => $this->getiduser(),
                ":desperson" => $this->getdesperson(),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => $this->getdespassword(),
                ":desemail" => $this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            )
        );
        $this->setData($results[0]);
    }

    /**
     * Função para excluir usuário
     * 
     * @return void 
     */
    public function delete()
    {
        $sql = new Sql();

        $results = $sql->query(
            "UPDATE tb_users SET deleted = 1 WHERE iduser = :iduser",
            array(
                ":iduser" =>  $this->getiduser()
            )
        );
    }

    /**
     * Função para enviar e-mail de recuperação de senha
     * 
     * @param string $email 
     * @return string
     */
    public static function getForgot($email)
    {

        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_persons p INNER JOIN tb_users u USING(idperson) WHERE p.desemail = :desemail AND u.deleted = 0",
            array(
                ":desemail" => $email
            )
        );

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        }

        $data = $results[0];

        $resultsRecovery =  $sql->select(
            "CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
            array(
                ":iduser" => $data["iduser"],
                ":desip" => $_SERVER["REMOTE_ADDR"]
            )
        );

        if (count($resultsRecovery) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        }

        $dataRecovery = $resultsRecovery[0];

        $ivlen = openssl_cipher_iv_length(self::SESS_CIPHER);
        $iv = substr(md5(self::getSecret()), 0, $ivlen);

        $ciphertext = openssl_encrypt($dataRecovery["idrecovery"], self::SESS_CIPHER, self::getSecret(), $options = OPENSSL_RAW_DATA, $iv);

        $code = base64_encode($ciphertext);

        $link = "http://www.ecommerce.com.br/admin/forgot/reset?code=$code";

        $mailer = new Mailer(
            $data['desemail'],
            $data['desperson'],
            "Redefinir Senha da Dale Store",
            "forgot",
            array(
                "name" => $data["desperson"],
                "link" => $link
            )
        );

        $mailer->send();

        return $data;
    }

    /**
     * Função para descriptografar e validar o código para recuperação de senha
     * 
     * @param string $code
     * @return string
     */
    public static function validForgotDecrypt($code)
    {
        $code = str_replace(" ", "+", $code);
        $ivlen = openssl_cipher_iv_length(self::SESS_CIPHER);
        $iv = substr(md5(self::getSecret()), 0, $ivlen);

        $decoded = base64_decode($code, TRUE);

        $idrecovery = openssl_decrypt($decoded, self::SESS_CIPHER, self::getSecret(), $options = OPENSSL_RAW_DATA, $iv);

        $sql = new Sql();
        $results = $sql->select(
            "SELECT * FROM tb_userspasswordsrecoveries ur 
            INNER JOIN tb_users u USING(iduser)
            INNER JOIN tb_persons p USING(idperson)
            WHERE ur.idrecovery  = :idrecovery 
                AND ur.dtrecovery is null 
                AND u.deleted = 0
                AND DATE_ADD(ur.dtregister, INTERVAL 1 HOUR) >= NOW();",
            array(
                ":idrecovery" => $idrecovery
            )
        );

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        }

        return $results[0];
    }

    /**
     * Função para invalidar recuperação de senha após ser usada
     * 
     * @param Int $idrecovery 
     * @return void 
     */
    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();

        $results = $sql->query(
            "UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
            array(
                ":idrecovery" => $idrecovery
            )
        );
    }

    /**
     * Função para retornar a frase secreta para criptografia
     * 
     * @return string 
     */
    public static function getSecret()
    {
        return getenv('DALE_SESSION_SECRET');
    }

    /**
     * Função para setar uma nova senha
     * 
     * @param string $password 
     * @return void 
     */
    public function setPassword($password)
    {
        $sql = new Sql();

        $results = $sql->query(
            "UPDATE tb_users SET despassword = :password WHERE iduser = :iduser",
            array(
                ":password" => $password,
                ":iduser" => $this->getiduser()
            )
        );
    }
}
