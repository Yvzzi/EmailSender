<?php
namespace mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use com\appxml\exception\FileNotExistException;
use com\appxml\exception\UnexpectedException;

/**
 * Introduce of Mail Config
 * Format: JSON
 * Options: mailer, host, password, SMTPSecure("tls" or "ssl"), SMTPAuth(true if need auth),
 *  port(default is 25), fromName, fromEmail, reply(Array of reply item(<Email> or [<Email>, <Name>] (Array)),
 *  charset(default is UTF-8)
 */
class MailAPI {
    private $mailer = null;
    private $host = null;
    private $username = null;
    private $password = null;
    private $SMTPSecure = null;
    private $SMTPAuth = null;
    private $port = null;
    private $fromName = null;
    private $fromEmail = null;
    private $reply = null;
    private $charset = null;

    private $phpMailer = null;

    public function __construct($configPath) {
        $this->initConfig($configPath);
        $this->phpMailer = new PHPMailer(true);
        $this->phpMailer->STMPDebug = 0;
        switch (strtolower($this->mailer)) {
            case "smtp":
                $this->phpMailer->isSMTP();
                break;
            case "mail":
                $this->phpMailer->isMail();
                break;
            case "sendmail":
                $this->phpMailer->isSendmail();
                break;
            case "gmail":
                $this->phpMailer->isQmail();
                break;
            default:
                $this->phpMailer->isSMTP();
        }
        $this->phpMailer->Host = $this->host;
        $this->phpMailer->SMTPAuth = $this->SMTPAuth;
        $this->phpMailer->Username = $this->username;
        $this->phpMailer->Password = $this->password;
        $this->phpMailer->SMTPAuth = $this->SMTPAuth;
        $this->phpMailer->SMTPSecure = $this->SMTPSecure;
        $this->phpMailer->Port = $this->port;
        $this->phpMailer->CharSet = $this->charset;
        if (!empty($this->fromEmail))
            $this->phpMailer->setFrom($this->fromEmail, $this->fromName);
        foreach ($this->reply as $v) {
            if (is_array($v)) {
                $this->phpMailer->addReplyTo(...$v);
            } else {
                $this->phpMailer->addReplyTo($v);
            }
        }
    }

    public function initConfig($path) {
        if (!file_exists($path)) throw new FileNotExistException("Can't find config");
        $json = json_decode(file_get_contents($path), true);
        if (is_null($json)) {
            throw new UnexpectedException("Config format is invalid");
        } else {
            // mail, smtp, sendmail, qmail
            $this->mailer = $json["mailer"] ?? "smtp";
            $this->host = $json["host"] ?? "";
            if (empty($this->host)) throw new UnexpectedException("Host is empty in config");
            $this->username = $json["username"] ?? "";
            if (empty($this->username)) throw new UnexpectedException("UserName is empty in config");
            $this->password = $json["password"] ?? "";
            if (empty($this->password)) throw new UnexpectedException("Password is empty in config");
            $this->SMTPSecure = $json["SMTPSecure"] ?? "ssl";
            $this->SMTPAuth = (bool) ($json["SMTPAuth"] ?? true);
            $this->port = (int) ($json["port"] ?? 25);
            $this->fromName = $json["fromName"] ?? "Mail Deliver System";
            if (empty($this->fromName)) throw new UnexpectedException("FromName is empty in config");
            $this->fromEmail = $json["fromEmail"] ?? "";
            if (empty($this->fromEmail)) throw new UnexpectedException("FromEmail is empty in config");
            $this->charset = $json["charset"] ?? "UTF-8";
            $this->reply = $json["reply"] ?? [];
        }
    }

    public function isHTML($bool = true) {
        $this->phpMailer->isHTML($bool);
    }

    public function addAddress(...$addresses) {
        foreach ($addresses as $addr) {
            if (is_array($addr)) {
                $this->phpMailer->addAddress(...$addr);
            } else {
                $this->phpMailer->addAddress($addr);
            }
        }
    }

    public function addCC(...$cc) {
        foreach ($cc as $v) {
            $this->phpMailer->addCC($v);
        }
    }

    public function addBCC(...$bcc) {
        foreach ($bcc as $v) {
            $this->phpMailer->addBCC($v);
        }
    }

    public function addAttachment(...$files) {
        foreach ($files as $f) {
            if (is_array($f)) {
                $this->phpMailer->addAttachment(...$f);
            } else {
                $this->phpMailer->addAttachment($f);
            }
        }
    }

    public function setSubject($subject) {
        $this->phpMailer->Subject = $subject;
    }

    public function setBody($body) {
        $this->phpMailer->Body = $body;
        $this->phpMailer->AltBody = strip_tags($body);
    }
    
    public function setAltBody($altBody) {
        $this->phpMailer->AltBody = $altBody;
    }

    public function send() {
        try {
            $this->phpMailer->send();
        } catch (PHPMailer\PHPMailer\Exception $e) {
            throw new UnexpectedException($e->ErrorInfo);
        }
    }
}
?>