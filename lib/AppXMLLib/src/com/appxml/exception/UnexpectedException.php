<?php
namespace com\appxml\exception;

class UnexpectedException extends \Exception {
    public function __construct($message = "") {
        parent::__construct($message);
    }
}
?>