<?php

namespace Dale;

use PHPMailer;
use Rain\Tpl;

class Mailer
{
    const NAME_FROM = "Dale Store";

    /** @var string E-mail do qual ira enviar */
    private $userEmail;

    /** @var string Senha e-mail  do qual ira enviar*/
    private $password;

    private $mail;

    public function __construct($toAddress, $toName, $subject, $tplName, $data = array())
    {

        $this->userEmail = getenv('DALE_MAIL_EMAIL');
        $this->password = getenv('DALE_MAIL_PASSWORD');

        $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . "/views/email/",
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/",
            "debug"         => false
        );

        Tpl::configure($config);

        $tpl = new Tpl;

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        $html = $tpl->draw($tplName, true);

        $this->mail = new \PHPMailer;

        $this->mail->isSMTP();

        $this->mail->SMTPDebug = 2;

        $this->mail->Host = 'smtp.gmail.com';

        $this->mail->Port = 587;

        $this->mail->SMTPSecure = 'tls';

        $this->mail->SMTPAuth = true;

        $this->mail->Username = $this->userEmail;

        $this->mail->Password = $this->password;

        $this->mail->setFrom($this->userEmail, Mailer::NAME_FROM);

        $this->mail->addAddress($toAddress, $toName);

        $this->mail->Subject = $subject;

        $this->mail->msgHTML($html);

        $this->mail->AltBody = '';
    }

    /**
     * Função para enviar e-mail
     * 
     * @return boolean
     */
    public function send()
    {

        return $this->mail->send();
    }
}
