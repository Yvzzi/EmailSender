<?php
require_once __DIR__ . "/../Autoload.php";

use mail\MailAPI;

function test6() {    
    $m = new MailAPI("mailSetting.json");
    $m->addAddress("2433988494@qq.com");
    $m->setSubject("Test Mail");
    $m->setBody("233");
    $m->send();
}
test6();
?>