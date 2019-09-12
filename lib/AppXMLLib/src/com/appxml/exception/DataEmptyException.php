<?php
namespace com\appxml\exception;

class DataEmptyException extends \Exception {
    public function __construct($message = "") {
        parent::__construct($message);
    }
}
?>