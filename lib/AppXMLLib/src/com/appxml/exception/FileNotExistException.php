<?php
namespace com\appxml\exception;

class FileNotExistException extends \Exception {
    public function __construct($message = "") {
        parent::__construct($message);
    }
}
?>