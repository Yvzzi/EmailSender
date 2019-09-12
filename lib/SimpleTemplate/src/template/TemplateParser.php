<?php
namespace template;

include_once __DIR__ . "/../../../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

class TemplateParser {
    private $spreadsheet;
    private $sheetArrayPool = [];
    
    const MODE_SINGLE = 0;
    const MODE_MULTIPLE = 1;
    
    public function loadJson($json):array {
        return json_decode(file_get_contents($json), true);
    }
    
    public function loadExcel($excel, $mainSheet = null, $mode = 0):array {
        $this->spreadsheet = IOFactory::load($excel);
        
        if (is_null($mainSheet))
            $mainSheet = $this->spreadsheet->getActiveSheet()->getTitle();
        
        $data = $this->loadSheetArrayLazy($mainSheet);
        
        if ($mode == self::MODE_SINGLE) $data = $data[0];
        return $data;
    }
    
    private function loadSheetArrayLazy($name) {
        if (isset($this->sheetArrayPool[$name])) {
            return $this->sheetArrayPool[$name];
        }
        
        $sheet = $this->spreadsheet->getSheetByName($name);
        $sheetArray = $sheet->toArray(null, true, true, true);
        if (count($sheetArray) < 1)
            throw new \ErrorException("Fail to parse the xlsx");
        
        $indeces = $sheetArray[1];
        $len = count($sheetArray);
        
        $data = [];
        for ($i = 2; $i <= $len; $i++) {
            if (count(array_diff($sheetArray[$i], [null])) === 0)
                continue;
            
            $tmp = [];
            foreach ($sheetArray[$i] as $k => $v) {
                if (is_string($v) && strpos($v, "&") === 0) {
                    $incellSheetData = $this->loadSheetArrayLazy(substr($v, 1));
                    
                    if (!isset($incellSheetData[$i - 2]))
                        throw new \ErrorException("Fail to parse the xlsx");
                    $v = $incellSheetData[$i - 2];
                } elseif (is_string($v) && strpos($v, "*") === 0) {
                    $incellSheetData = $this->loadSheetArrayLazy(substr($v, 1));
                    
                    if (!isset($incellSheetData[$i - 2]))
                        throw new \ErrorException("Fail to parse the xlsx");
                    $v = $incellSheetData;
                }
                if (is_string($v)) {
                    $v = str_replace("\\&", "&", $v);
                    $v = str_replace("\\*", "*", $v);
                }
                $tmp[$indeces[$k]] = $v;
            }
            array_push($data, $tmp);
        }
        
        $this->sheetArrayPool[$name] = $data;
        return $data;
    }
    
    public function loadYaml($yaml) {
        return yaml_parse(file_get_contents($yaml));
    }
    
    public static function buildTag(array $snippets) {
        return "<?php " . implode("", $snippets) . " ?>";
    }
    
    public static function parseTemplate($str, $path = ".") {
        // foreach
        $str = preg_replace('/{{\s*each\s+([^\r\n]+)\s*}}/U',self::buildTag([
            'foreach($1 as $key => $value) {'
        ]), $str);
        // for
        $str = preg_replace('/{{\s*for\s+([^\r\n]+)\s*}}/U', self::buildTag([
            'for ($1) {'
        ]), $str);
        // elseif
        $str = preg_replace('/{{\s*elseif\s+([^\r\n]+)\s*}}/U', self::buildTag([
            '} elseif ($1) {'
        ]), $str);
        // if
        $str = preg_replace('/{{\s*if\s+([^\r\n]+)\s*}}/U', self::buildTag([
            'if ($1) {'
        ]), $str);
        // else
        $str = preg_replace('/{{\s*else\s*}}/U', self::buildTag([
            '} else {'
        ]), $str);
        // end
        $str = preg_replace('/{{\s*end\s*}}/U', self::buildTag([
            '}'
        ]), $str);
        // endfun
        $str = preg_replace('/{{\s*endf\s*}}/U', self::buildTag([
            '}}'
        ]), $str);
        // fun block
        $str = preg_replace('/{{\s*function\s+(\w+)\\(([^\r\n]*)\\)\s*:\s*}}/U', self::buildTag([
            'if (!function_exists("$1")){',
            'function $1($2) {'
        ]), $str);
        // block keyword
        $str = preg_replace('/{{\s*([^\r\n]*)\s*:\s*}}/U', self::buildTag([
            '$1 {'
        ]), $str);
        // statement
        $str = preg_replace('/\\{\\{\\s*:\\s*([^\\r\\n]+)\\s*\\}\\}/U', self::buildTag([
            '$1;'
        ]), $str);
        // child template
        $str = preg_replace('/{{\s*#\s*([^\r\n\s]+)\s+([^\r\n\s]+)\s*}}/U', self::buildTag([
            'echo ${"#TMPL"}($1, "' . $path . "/" . '" . $2);'
        ]), $str);
        // debug
        $str = preg_replace('/{{\s*\?\s*([^\r\n]+)\s*}}/U', self::buildTag([
            'var_dump($1);',
        ]), $str);
        // assert
        $str = preg_replace('/{{\s*@\s*([^\r\n]+)\s*}}/U', self::buildTag([
            'assert($1);',
        ]), $str);
        // format
        $str = preg_replace('/{{\s*%\s*([^\r\n\s]+)((?:\s+[^\r\n\s]+)+)}}/U', self::buildTag([
            'printf($1, ...(explode(" ", trim("$2"))));',
        ]), $str);
        // echo value
        $str = preg_replace('/{{\s*([^\r\n]+)\s*}}/U', self::buildTag([
            '${"#ECHO"}($1);',
        ]), $str);
        // implict hide the line
        $els = preg_split('/(\r\n|\n)/', $str);
        // trim blanks
        $els = preg_replace('/^([ \t]*)<\?php\s+(?!echo[^\r\n]+\s*\?>$)/U', '<?php ', $els);
        $str = implode("\n", $els);
        // add namespace
        $str = "<?php" . "\n"
        . "namespace " . __NAMESPACE__ . "\\TemplateParser\\code;" . "\n"
        . "use " . __NAMESPACE__ . "\\TemplateParser;" . "\n"
        . "?>" . "\n"
        . $str;
        // escape characters
        $str = str_replace("\\{", "{", $str);
        $str = str_replace("\\}", "{", $str);
        
        return $str;
    }
    
    private static function print($var):void {
        if (is_bool($var)) $var = $var ? "true" : "false";
        if (is_array($var)) $var = implode(", ", $var);
        if (is_object($var)) $var = "[object]";
        echo $var;
    }
    
    public static function formatTemplate(array $data, string $template, $path = ".") {
        ${"##tempFile"} = tempnam(sys_get_temp_dir(), 'Template');
        file_put_contents(${"##tempFile"}, self::parseTemplate($template, $path));
        ob_start();
        
        // PS: call_user_func or $var()
        // cannot automatically add namespace
        ${"#TMPL"} = __NAMESPACE__ . "\\TemplateParser::format";
        ${"#ECHO"} = __NAMESPACE__ . "\\TemplateParser::print";
        
        foreach ($data as $k => $v) ${$k} = $v;
        
        include_once(${"##tempFile"});
        
        unlink(${"##tempFile"});
        ${"##ret"} = ob_get_contents();
        ob_end_clean();
        return ${"##ret"};
    }
    
    public static function format(array $data, string $tmplPath, $mode = 0) {
        $dir = str_replace("\\", "/", dirname(str_replace("\\", "/", $tmplPath)));
        $content = file_get_contents($tmplPath);
        if ($mode == 0) {
            return self::formatTemplate($data, $content, $dir);
        } else {
            $ret = [];
            foreach ($data as $k => $v) {
                $ret[$k] = self::formatTemplate($v, $content, $dir);
            }
            return $ret;
        }
    }
}
?>