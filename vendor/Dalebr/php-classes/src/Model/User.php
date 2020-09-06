<?php

namespace Dale\Model;

use \Dale\DB\Sql;
use \Dale\Mailer;
use \Dale\Model;

class User extends Model
{
    const SESSION = "User";
    const SESS_CIPHER = 'BF-ECB';
    const ERROR = 'UserError';

    /**
     * Função para retornar o usuário da sessão
     * 
     * @return object User
     */
    public static function getFromSession()
    {

        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;
    }

    /**
     * Função para verificar se o usuário está logado e está tentando acessar uma rota de administração
     * 
     * @param boolean $inadmin Deve ser TRUE se está navegando na administração
     * @return object User
     */
    public static function checkLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ) {
            //Não está logado
            return false;
        }

        if ($inadmin  && (bool)$_SESSION[User::SESSION]['inadmin']) {
            // Admin e está logado
            return true;
        }

        return !$inadmin;
    }

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
            "SELECT * FROM tb_users u
            INNER JOIN tb_persons p ON u.idperson = p.idperson
            WHERE u.deslogin = :LOGIN AND u.deleted = 0",
            array(
                ":LOGIN" => $login
            )
        );

        if (count($results) === 0) {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if (!password_verify($password, $data["despassword"])) {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }
        $user = new User();

        $data['desperson'] = utf8_encode($data['desperson']);

        $user->setData($data);

        $_SESSION[User::SESSION] = $user->getValues();

        return $user;
    }

    /**
     * Função para verificar se o usuário está logado e está tentando acessar uma rota de administração. Else redireciona para login
     * 
     * @param boolean $inadmin
     * @return void 
     */
    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
                exit;
            }
            header("Location: /login");
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
                ":desperson" => utf8_decode($this->getdesperson()),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => User::getPasswordHash($this->getdespassword()),
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

        $data = $results[0];

        $data['desperson'] = utf8_encode($data['desperson']);

        $this->setData($data);
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
                ":desperson" => utf8_decode($this->getdesperson()),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => User::getPasswordHash($this->getdespassword()),
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
            "UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery;",
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
            "UPDATE tb_users SET despassword = :password WHERE iduser = :iduser;",
            array(
                ":password" => User::getPasswordHash($password),
                ":iduser" => $this->getiduser()
            )
        );
    }

    /**
     * Função para armazenar os erros na sessão
     * 
     * @param string $errorMessage
     */
    public static function setError($errorMessage)
    {
        $_SESSION[User::ERROR] = $errorMessage;
    }

    /**
     * Função para resgatar os erros na sessão
     * 
     * @return string
     */
    public static function getError()
    {
        $errorMessage =  (isset($_SESSION[User::ERROR])) ?  $_SESSION[User::ERROR] : '';

        User::clearError();

        return $errorMessage;
    }

    /**
     * Função para limpar os erros da sessão
     */
    public static function clearError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    /**
     * Função para encriptografar a senha
     * 
     * @param string $password
     * @return string
     */
    public static function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            "cost" => 12
        ]);
    }
}
