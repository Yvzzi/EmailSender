<?php
namespace com\appxml\exception;

class IllegalAccessException extends \Exception {
    public function __construct($message = "") {
        parent::__construct($message);
    }
}
?>