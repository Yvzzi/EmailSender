<?php
/**
 *  @file packagerCli.php
 *  @brief A easy tool
 *  @author Yvzzi
 */

require_once __DIR__ . "/../Autoload.php";

use phar\PharBuilder;
use com\appxml\util\FileIO;

const USAGE = <<<EOF
Usage: php composer.php <action> <args> [option]
    action:
        -u <path>   Unphar a archive
        -p <path>   Phar a archive
    args:
        -o <path>   Specific name of output
        -i <path>   Specific config that provides ignore files or dirs
    option:
        -z          Output with zip format

EOF;
$params = getopt("u:p:zo:i:");
if (!is_array($params) || !(isset($params["u"]) || isset($params["p"]))) {
    echo USAGE;
    exit(0);
}

if (isset($params["u"])) {
    PharBuilder::unphar(FileIO::getAbsolutePath($params["u"]), FileIO::getAbsolutePath($params["o"]), isset($params["z"]));
} else {
    $ignore = [];
    if (isset($params["i"])) {
        $ignore = file_get_contents(FileIO::getAbsolutePath($params["i"]));
        $ignore = str_replace("\r\n", "\n", $ignore);
        $ignore = explode("\n", $ignore);
        $ignore = array_map(function($v) {
            return trim($v);
        }, $ignore);
    }
    $pharBuilder = new PharBuilder(FileIO::getAbsolutePath($params["p"]), $ignore);
    fwrite(STDOUT, "Please input stub of cli:\n");
    $stubCli = trim(fgets(STDIN));
    fwrite(STDOUT, "Please input stub of web:\n");
    $stubWeb = trim(fgets(STDIN));
    
    $pharBuilder->setDefaultStub($stubCli, $stubWeb);
    $pharBuilder->build($params["o"] ?? "output.phar");
}
?>