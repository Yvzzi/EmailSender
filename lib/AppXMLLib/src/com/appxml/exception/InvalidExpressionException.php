<?php
namespace com\appxml\exception;

class InvalidExpressionException extends \Exception {
    public function __construct($message = "") {
        parent::__construct($message);
    }
}