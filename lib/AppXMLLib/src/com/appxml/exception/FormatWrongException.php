<?php
namespace com\appxml\exception;

class FormatWrongException extends \Exception {
    public function __construct($message = "") {
        parent::__construct($message);
    }
}
?>