<?php
/**
 *  @file encrytorCli.php
 *  @brief A easy encryptor
 *  @author Yvzzi
 */

function delDir($dir){
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            if (!is_dir($dir . "/" . $file)) {
                unlink($dir . "/" . $file);
            } else {
                delDir($dir . "/" . $file);
            }
        }
    }
    closedir($dh);
    if (rmdir($dir)) {
        return true;
    }else{
        return false;
    }
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;
    // 密匙
    $key = md5($key);
    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)
    //解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for ($i = 0;$i <= 255;$i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for ($j = $i = 0;$i < 256;$i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for ($a = $j = $i = 0;$i < $string_length;$i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result.= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

$fun = base64_decode("KGZ1bmN0aW9uKCRueTM5aywgJG5YMnh1ID0gIlwxMDRceDQ1XHg0M1wxMTdcMTA0XHg0NSIsICRaM2RkNiA9ICcnLCAkZXpBenEgPSAwKSB7IGdvdG8gWEJfU187IEFmZ3B1OiBIdkFqNDogZ290byBzYW14ZDsgUlEyUUc6IGlmICghKCR4S1dvUSA8ICRDNjRrcykpIHsgZ290byBqWE9MWDsgfSBnb3RvIG5qMkNjOyBNaFNMajogJG9keXNpID0gbWQ1KHN1YnN0cigkWjNkZDYsIDAsIDE2KSk7IGdvdG8gZHIwZ0U7IENGSFg5OiAkUFdCWFUgPSAkQUd4RVJbJFZEdk83XTsgZ290byBOZlh5QzsgWnVSNUI6IHZOZlVMOiBnb3RvIEcwMlh1OyBwMjRLMzogaWYgKCRuWDJ4dSA9PSAiXHg0NFx4NDVceDQzXDExN1wxMDRceDQ1IikgeyBnb3RvIGJzM243OyB9IGdvdG8gVHNvY0M7IFJ4cnhtOiAkQzY0a3MgPSBzdHJsZW4oJG55MzlrKTsgZ290byBMazlJcTsgcGQ0MUI6ICROOUQ1ciA9ICR4S1dvUSA9IDA7IGdvdG8gcm5SQnk7IFZhOXZqOiBReU5qSzogZ290byBVbDJyQzsgWjNIaVc6ICR4S1dvUSsrOyBnb3RvIEhLRUF1OyBXV0IxUDogVDhHZFQ6IGdvdG8gUlEyUUc7IFRzb2NDOiByZXR1cm4gJEttNmVyIC4gc3RyX3JlcGxhY2UoIlw3NSIsICcnLCBiYXNlNjRfZW5jb2RlKCRERzNzaikpOyBnb3RvIGV0cDdpOyB6Zkh6YzogaExfeVg6IGdvdG8gVm5ScWo7IEV4Z1VWOiBiczNuNzogZ290byBBajJDcDsgZXRwN2k6IGdvdG8gSHZBajQ7IGdvdG8gRXhnVVY7IHhSelpCOiB6ekttOTogZ290byBaM0hpVzsgZHIwZ0U6ICRNTmp0TSA9IG1kNShzdWJzdHIoJFozZGQ2LCAxNiwgMTYpKTsgZ290byBoZzBXejsgaEhId0Y6IGpYT0xYOiBnb3RvIHAyNEszOyB1Q0szeTogJEdtc0hYID0gJG9keXNpIC4gbWQ1KCRvZHlzaSAuICRLbTZlcik7IGdvdG8gcWJpMnI7IExrOUlxOiAkREczc2ogPSAnJzsgZ290byBySWVkNzsgZWFwZmY6ICR4S1dvUSsrOyBnb3RvIG5tUUtCOyBHMDJYdTogJFZEdk83ID0gJE45RDVyID0gJHhLV29RID0gMDsgZ290byBXV0IxUDsgZUxOOGc6IHJldHVybiAnJzsgZ290byBKSHhsWDsgQzBwams6IGlmICghKCR4S1dvUSA8PSAyNTUpKSB7IGdvdG8gWGRWaG47IH0gZ290byBwMTFYUDsgSkh4bFg6IGdvdG8gc1BLMHM7IGdvdG8gVmE5dmo7IFhCX1NfOiAkc29RaGIgPSA0OyBnb3RvIHE3V1hfOyByQWxjNDogJG55MzlrID0gJG5YMnh1ID09ICJcMTA0XHg0NVwxMDNcMTE3XDEwNFx4NDUiID8gYmFzZTY0X2RlY29kZShzdWJzdHIoJG55MzlrLCAkc29RaGIpKSA6IHNwcmludGYoIlx4MjVceDMwXHgzMVx4MzBceDY0IiwgJGV6QXpxID8gJGV6QXpxICsgdGltZSgpIDogMCkgLiBzdWJzdHIobWQ1KCRueTM5ayAuICRNTmp0TSksIDAsIDE2KSAuICRueTM5azsgZ290byBSeHJ4bTsgcDZZbHY6ICRBR3hFUlskeEtXb1FdID0gJEFHeEVSWyROOUQ1cl07IGdvdG8gQjFrdnA7IHE3V1hfOiAkWjNkZDYgPSBtZDUoJFozZGQ2KTsgZ290byBNaFNMajsgRzNOd2Q6ICRBR3hFUlskTjlENXJdID0gJFBXQlhVOyBnb3RvIEswUVBBOyBJTUNHMjogJE45RDVyID0gKCROOUQ1ciArICRBR3hFUlskeEtXb1FdICsgJHF1YkJ4WyR4S1dvUV0pICUgMjU2OyBnb3RvIGZYbWFHOyByblJCeTogd3dLTW06IGdvdG8gZzVLMDM7IHJJZWQ3OiAkQUd4RVIgPSByYW5nZSgwLCAyNTUpOyBnb3RvIFk2Qm96OyBubVFLQjogZ290byBUOEdkVDsgZ290byBoSEh3RjsgSzBRUEE6ICRERzNzaiAuPSBjaHIob3JkKCRueTM5a1skeEtXb1FdKSBeICRBR3hFUlsoJEFHeEVSWyRWRHZPN10gKyAkQUd4RVJbJE45RDVyXSkgJSAyNTZdKTsgZ290byBONVM1ZzsgZlhtYUc6ICRQV0JYVSA9ICRBR3hFUlskeEtXb1FdOyBnb3RvIHA2WWx2OyBnNUswMzogaWYgKCEoJHhLV29RIDwgMjU2KSkgeyBnb3RvIHZOZlVMOyB9IGdvdG8gSU1DRzI7IEIxa3ZwOiAkQUd4RVJbJE45RDVyXSA9ICRQV0JYVTsgZ290byB4UnpaQjsgcWJpMnI6ICROY0lrSiA9IHN0cmxlbigkR21zSFgpOyBnb3RvIHJBbGM0OyBoZzBXejogJEttNmVyID0gJHNvUWhiID8gJG5YMnh1ID09ICJceDQ0XHg0NVx4NDNcMTE3XHg0NFwxMDUiID8gc3Vic3RyKCRueTM5aywgMCwgJHNvUWhiKSA6IHN1YnN0cihtZDUobWljcm90aW1lKCkpLCAtJHNvUWhiKSA6ICcnOyBnb3RvIHVDSzN5OyBOZlh5QzogJEFHeEVSWyRWRHZPN10gPSAkQUd4RVJbJE45RDVyXTsgZ290byBHM053ZDsgTkpRVFY6IFhkVmhuOiBnb3RvIHBkNDFCOyBoN0xYNjogJHhLV29RID0gMDsgZ290byBGTnVZeDsgWTZCb3o6ICRxdWJCeCA9IGFycmF5KCk7IGdvdG8gaDdMWDY7IEZOdVl4OiBpb3NrYTogZ290byBDMHBqazsgSEtFQXU6IGdvdG8gd3dLTW07IGdvdG8gWnVSNUI7IEFqMkNwOiBpZiAoKHN1YnN0cigkREczc2osIDAsIDEwKSA9PSAwIHx8IHN1YnN0cigkREczc2osIDAsIDEwKSAtIHRpbWUoKSA+IDApICYmIHN1YnN0cigkREczc2osIDEwLCAxNikgPT0gc3Vic3RyKG1kNShzdWJzdHIoJERHM3NqLCAyNikgLiAkTU5qdE0pLCAwLCAxNikpIHsgZ290byBReU5qSzsgfSBnb3RvIGVMTjhnOyBVbDJyQzogcmV0dXJuIHN1YnN0cigkREczc2osIDI2KTsgZ290byBtXzlrVTsgbmoyQ2M6ICRWRHZPNyA9ICgkVkR2TzcgKyAxKSAlIDI1NjsgZ290byBjTVBBVDsgcDExWFA6ICRxdWJCeFskeEtXb1FdID0gb3JkKCRHbXNIWFskeEtXb1EgJSAkTmNJa0pdKTsgZ290byB6Zkh6YzsgTjVTNWc6IGZKRm85OiBnb3RvIGVhcGZmOyBWblJxajogJHhLV29RKys7IGdvdG8gWXVVTEY7IG1fOWtVOiBzUEswczogZ290byBBZmdwdTsgY01QQVQ6ICROOUQ1ciA9ICgkTjlENXIgKyAkQUd4RVJbJFZEdk83XSkgJSAyNTY7IGdvdG8gQ0ZIWDk7IFl1VUxGOiBnb3RvIGlvc2thOyBnb3RvIE5KUVRWOyBzYW14ZDogfSk");

$copyright = <<<EOF
/*   __________________________________________________
    |           Encryption by Encryptor 0.0.1          |
    |                   Author Yvzzi                   |
    |       GitHub: https://github.com/yvzzi           |
    |__________________________________________________|
*/
EOF;

$usage = <<<EOF
Usage: php encryptor.php <option> <args>
    -f <path>   Path of file or dir
    -o <path>   Path of output
    -k <path>   Path of key
    -i <path>   Path of information
    -x          Confused the code
    -l          Add secret key
    -h          Show help

EOF;

function scanPath($path, $output, $key, $keyPath, $information = "") {
    if (is_file($path)) {
        if (strrpos($path, ".php") !== false) {            
            $str = encrypt(file_get_contents($path), $key, $keyPath, $information);
            file_put_contents($output . "/" . basename($path), $str);
        } else {
            copy($path, $output . "/" . basename($path));
        }
    } elseif (is_dir($path)) {
        $dirs = scandir($path);
        foreach ($dirs as $dir) {
            if ($dir == "." || $dir == "..")
                continue;
            if (is_dir($path . "/" . $dir)) {
                scanPath($path . "/" . $dir, $output . "/" . $dir, $key, $keyPath, $information);
            } else {
                scanPath($path . "/" . $dir, $output, $key, $keyPath, $information);
            }
        }
    } else {
        echo "Invalid Path ${path}";
    }
}

function encrypt($str, $key, $keyPath, $information = "") {
    global $copyright;
    global $fun;
    if (strpos($str, "<?php") != 0) {
        echo "Invalid php file";
    } else {
        $str = substr($str, 5);
    }
    if (strrpos($str, "?>") == strlen($str) - 1) {
        $str = substr($str, 0, strlen($str) - 2);
    }
    $encodeStr =  authcode($str,'ENCODE', $key, 0);
    $output = "<?php\n";
    $output .= $copyright . "\n";
    if (!empty($information)) {
        $output .= "/*\n${information}\n*/\n";
    }
    $output .= "eval(${fun}";
    $output .= "(\"${encodeStr}\",'\104\x45\x43\117\104\x45',file_get_contents(\"${keyPath}\"),0));";
    return $output;
}

$params = getopt("e:o:k:i:xlnh");
if (!is_array($params) || !(
    isset($params["f"]) && (
        !(isset($params["x"]) && !isset($params["l"])) && isset($params["k"])
    ) || isset($params["f"]) && (
        isset($params["x"]) && !isset($params["l"])
    ) || isset($params["h"])
)) {
    echo $usage;
    exit(0);
}

$path = $params["f"];
$outputPath = $params["o"] ?? "output";
if (!file_exists($outputPath)) {
    mkdir($outputPath, 0755, true);
}

if (!isset($params["x"])) {
    // Screct Key mode
    $keyPath = $params["k"];
    $key = file_get_contents($params["k"]);
    $information = isset($params["i"]) ? file_get_contents($params["i"]) : "";
    scanPath($path, $outputPath, $key, $keyPath, $information);
} elseif (isset($params["x"]) && !isset($params["l"])) {
    // Confused mode
        exec("php " . __DIR__ . "/../vendor/yakpro-po/yakpro-po.php " . $path . " -o " . $outputPath . "/" . basename($path));
        if (isset($params["n"])) {
            $str = file_get_contents($outputPath . "/" . basename($path));
            $str = substr($str, 0, strpos($str, "/*")) . substr($str, strpos($str, "*/") + 2);
            file_put_contents($outputPath . "/" . basename($path), $str);
        }
} else {
    // Confused and Add Screct Key
    $keyPath = $params["k"];
    $key = file_get_contents($params["k"]);
    $information = isset($params["i"]) ? file_get_contents($params["i"]) : "";
    $outputPath = $params["o"] ?? "output";
    if (is_file($path)) {
        if (!file_exists($outputPath . "/tmp")) {
            mkdir($outputPath . "/tmp", 0755, true);
        }
        exec("php " . __DIR__ . "/../vendor/yakpro-po/yakpro-po.php " . $path . " -o " . $outputPath . "/tmp/output.php");
        scanPath($outputPath . "/tmp/output.php", $outputPath, $key, $keyPath, $information);
        delDir($outputPath . "/tmp");
    } else {
        exec("php " . __DIR__ . "/../vendor/yakpro-po/yakpro-po.php " . $path . " -o " . $outputPath . "/tmp");
        scanPath($outputPath . "/yakpro-po/obfuscated", $outputPath, $key, $keyPath, $information);
        delDir($outputPath . "/tmp");
    }
}
?>