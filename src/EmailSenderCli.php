<?php

require_once __DIR__ . "/../lib/MailAPI/Autoload.php";
require_once __DIR__ . "/../lib/SimpleTemplate/Autoload.php";
require_once __DIR__ . "/../lib/AppXMLLib/Autoload.php";

use mail\MailAPI;
use template\TemplateParser;

function sendEmail($config, $mailSetting, $output = null) {
    $config = json_decode(file_get_contents($config), true);
    if ($config == null)
        throw new \ErrorException("Invalid config");
    
    // load data
    $active = null;
    $index = strrpos($config["data"], "@");
    if ($index !== false) {
        $active = substr($config["data"], $index + 1);
        $config["data"] = substr($config["data"], 0, $index);
    }
    $index = strrpos($config["data"], ".");
    if ($index === false)
        throw new \ErrorException("Invalid field data in config");
    $subfix = substr($config["data"], $index + 1);
    
    switch ($subfix) {
        case "xlsx":
            $data = (new TemplateParser())->loadExcel($config["data"], $active, $config["mode"]  == "single" ? TemplateParser::MODE_SINGLE : TemplateParser::MODE_MULTIPLE);
            break;
        case "json":
        case "js":
            $data = (new TemplateParser())->loadJson($config["data"]);
            break;
        case "yaml":
        case "yml":
            $data = (new TemplateParser())->loadYaml($config["data"]);
            break;
        default:
            throw new \ErrorException("Invalid field data in config");
    }
    
    // fix iterator
    if ($config["mode"] == "single") {
        $data = [$data];
    }
    
    // send email
    foreach ($data as $k => $v) {
        $m = new MailAPI($mailSetting);
        
        if (!empty($config["primaryKey"]))
            echo "> Finished task of " . $v[$config["primaryKey"]] . PHP_EOL;
        
        // get magic key
        $to = $config["to"];
        foreach ($to as $i => $e) {
            $to[$i] = TemplateParser::formatTemplate($v, $e);
        }
        $subject = TemplateParser::formatTemplate($v, $config["subject"]);
        $attachment = $config["attachment"];
        foreach ($attachment as $i => $e) {
            $attachment[$i] = TemplateParser::formatTemplate($v, $e);
        }
        
        // complie body
        $body = TemplateParser::format($v, $config["template"]);
        
        if (!is_null($output)) {
            // output mode
            if (!is_dir($output)) {
                mkdir($output, 0755, true);
            }
            $id = empty($config["primaryKey"]) ? uniqid("", true) : $v[$config["primaryKey"]];
            file_put_contents($output . "/" . $id . ".html", $body);
            
            $info = [
                "to" => [],
                "subject" => "",
                "attachment" => []
            ];
            $tmp = [];
            foreach ($to as $e) {
                if (empty(trim($e))) continue;
                array_push($tmp, $e);
            }
            $info["to"] = $tmp;
            $tmp = [];
            foreach ($attachment as $e) {
                if (empty(trim($e))) continue;
                array_push($tmp, $e);
            }
            $info["attachment"] = $tmp;
            file_put_contents($output . "/" . $id . "-info.json", json_encode($info));
        } else {
            // send mode
            foreach ($to as $e) {
                if (empty(trim($e))) continue;
                $m->addAddress($to);
            }
            $m->setSubject($subject);
            $m->setBody($body);
            foreach ($attachment as $e) {
                if (empty(trim($e))) continue;
                $m->addAttachment($e);
            }
            $m->send();
            sleep(rand((int) $config["interval"][0], (int) $config["interval"][1]));
        }
    }
}

const VERSION = "EmailSender Cli by Yvzzi v1.1.0";
const USAGE = <<<EOF
php %s <option> <argument>
    -h              Get help
    -v              Get Version
    -c <filename>   Specific mail setting file
    -f <filename>   Send email with task specific file
    -n <dirname>    Batch processine to tasks
    -o <dirname>    Output email to dir instead of sending

EOF;
$option = getopt("f:o:c:hv");

$output = $option["o"] ?? null;
$mailSetting = "";

if (isset($option["v"])) {
    echo VERSION . PHP_EOL;
} elseif (isset($option["h"])) {
    echo VERSION . PHP_EOL;
    printf(USAGE, $argv[0]);
} else {
    if (!isset($option["c"])) {
        echo "Dosen't specific mail-setting file" . PHP_EOL;
        exit(0);
    } else {
        $mailSetting = $option["c"];
    }
    if (isset($option["f"])) {
        sendEmail($option["f"], $mailSetting, $output);
    } elseif (isset($option["n"])) {
        $files = scandir($option["n"], $mailSetting, $output);
        foreach ($files as $f) {
            sendEmail($f, $mailSetting, $output);
        }
    } else {
        printf(USAGE, $argv[0]);
    }
}

?>