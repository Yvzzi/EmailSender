<?php

include_once __DIR__ . "/../Autoload.php";

use template\TemplateParser;

// 通过excel加载数据
$data = (new TemplateParser())->loadExcel("data.xlsx" , false, TemplateParser::MODE_SINGLE);

// 通过json加载数据
// $data = (new TemplateParser())->loadJson("data.json");

// 直接提供数据
// $data = array_merge($data, [
    // "detail" => [
        // "a" => 1,
        // "b" => 2
    // ]
// ]);

// var_dump($data);

// 第一个参数是数据 第二个参数是模板字符串 而直接编译模板
// $a = TemplateParser::formatTemplate($data, "模板");

// 第二个参数是模板文件路径 通过文件提供模板然后编译
$a = TemplateParser::format($data, "b.tmpl");

// 输出编译后模板
echo $a;