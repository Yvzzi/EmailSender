<?php
/**
 *  @file packagerJavaCli.php
 *  @brief Easy comsoper of java
 *  @author Yvzzi
 */

$USAGE = <<<EOF
Usage: php composer-java.php [option <arg>]
    -n <path>   Specific path of java project, default is current path
    -o <path>   Specific path of jar, default is current path
    -c <path>   Specific path of MANIFEST.MF, default is current path
    -c init     Generate a MANIFEST.MF
    -c none     None MANIFEST.MF will be included
    -l <path>   Specific dir of libs
    -d          Clear build
    -h          Show help

EOF;

$content = <<<EOF
Manifest-Version: 1.0
Created-By: 1.8.0
Main-Class: {MainPath}
Class-Path: {LibPath}

EOF;

$param = getopt("n:c:o:dhl:");

function scan(string $path, array &$arr, $relative = "."){
    $dh = opendir($path);
    while(($file = readdir($dh)) !== false){
        if($file == '.' || $file == '..')
            continue;
        if(is_dir($path . '/' . $file)){
            scan($path . '/' . $file, $arr, $relative . "/" . $file);
        } else {
            array_push($arr, $relative . "/" . $file);
        }
    }
}

function delDir($dir) {
   $dh=opendir($dir);
   while ($file = readdir($dh)) {
      if ($file != "." && $file != "..") {
         $fullpath=$dir . "/" . $file;
         if (!is_dir($fullpath)) {
            unlink($fullpath);
         } else {
            delDir($fullpath);
         }
      }
   }
   closedir($dh);
   if (rmdir($dir)) {
      return true;
   } else {
      return false;
   }
}

if (isset($param["h"])) {
    echo $USAGE;
    exit(0);
}

$currentPath = $param["n"] ?? getcwd();
if (!file_exists($currentPath . "/src") || !file_exists($currentPath . "/src/main") || !file_exists($currentPath . "/src/main/resources")) {
    echo "Current compile path is not a valid project path.\n";
    exit(1);
}

if (isset($param["d"])) {
    if (file_exists($currentPath . "/bin")) {
        delDir($currentPath . "/bin");
    }
    echo "> Finished.\n";
    exit(0);
}

if (!file_exists($currentPath . "/bin"))
    mkdir($currentPath . "/bin", 0755);
$ordJavaFiles = [];
scan($currentPath . "/src/main", $ordJavaFiles);
$javaFiles = [];
foreach ($ordJavaFiles as $f) {
    if (strpos($f, "./resources") !== 0) {
        array_push($javaFiles, $f);
    }
}
$resources = [];
scan($currentPath . "/src/main/resources", $resources);


$str = "";
foreach ($javaFiles as $f) {
    $str .= $currentPath . "/src/main/" . $f . "\n";
}
$str = trim($str);
echo "> Building java sources...\n";
file_put_contents($currentPath . "/tmp.list", $str);
$libPath = isset($param["l"]) ? (preg_match("/^([A-Z]:|\/)/", $param['l']) != false ? $param["l"] : getcwd() . "/" . $param["l"]) : "";
$libPath = empty($libPath) ? "" : $libPath . PATH_SEPARATOR . $libPath . "/*" . PATH_SEPARATOR;
exec("javac -encoding utf-8 -d ${currentPath}/bin -cp " . $libPath . "${currentPath}/lib" . PATH_SEPARATOR . "${currentPath}/lib/* @${currentPath}/tmp.list");
unlink($currentPath . "/tmp.list");

foreach ($resources as $f) {
    copy($currentPath . "/src/main/resources/" . $f, $currentPath . "/bin/". $f);
}

$output = $param["o"] ?? "output.jar";
if (preg_match("/^(\/|[A-Z]:)/", $output) == false) {
    $output = getcwd() . "/" . $output;
}
if (isset($param["c"]) && $param["c"] == "init") {
    file_put_contents($currentPath . "/MANIFEST.MF", $content);
    echo("> Finished.\n");
} else if (isset($param["c"]) && $param["c"] != "none") {
    chdir($currentPath . "/bin");
    exec("jar cvfm ${output} ${param['c']} .");
    echo("> Finished.\n");
} else if (isset($param["c"]) && $param["c"] == "none") {
    chdir($currentPath . "/bin");
    exec("jar cvf ${output} .");
    echo("> Finished.\n");
} else {
    $path = getcwd();
    fwrite(STDOUT, "Please input Main Class:\n");
    $content = str_replace("{MainPath}", trim(fgets(STDIN)), $content);
    fwrite(STDOUT, "Please input Lib Path:\n");
    $content = str_replace("{LibPath}", trim(fgets(STDIN)), $content);
    file_put_contents($currentPath . "/MANIFEST.MF", $content);
    chdir($currentPath . "/bin");
    exec("jar cvfm ${output} ../MANIFEST.MF .");
    chdir($path);
    unlink($currentPath . "/MANIFEST.MF");
    echo("> Finished.\n");
}
?>