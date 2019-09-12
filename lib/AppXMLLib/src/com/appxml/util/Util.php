<?php
namespace com\appxml\util;

class Util {
	public static function urlFormat($path):string {
		// transform for dos to *nix style
		$path=str_replace('\\', '/', $path);
		// replace /xxx/../ in $path to /
		$last = '';
		while ($path != $last) {
			$last = $path;
			$path = preg_replace('/\/[^\/]+\/\.\.\//', '/', $path);
		}
		// replace ./ and //
		$last = '';
		while ($path != $last) {
			$last = $path;
			$path = preg_replace('/([\.\/]\/)+/', '/', $path);
		}
		return $path;
	}
	/**
	 * Json Indented format
	 * @param array | object $json
	 * @return string
	 */
	public static function jsonIndentFormat($jsonStr):string {
		$result = '';
		$indentCount = 0;
		$strLen = strlen($jsonStr);
		$indentStr = '    ';
		$newLine = "\n";
		$isInQuotes = false;
		$prevChar = '';
		for($i = 0; $i <= $strLen; $i++) {
			$char = substr($jsonStr, $i, 1);
 
			if($isInQuotes){
				$result .= $char;
				if(($char=='"' && $prevChar!='\\')){
					$isInQuotes = false;
				}
			}
			else{
				if(($char=='"' && $prevChar!='\\')){
					$isInQuotes = true;
					if ($prevChar != ':'){
						$result .= $newLine;
						for($j = 0; $j < $indentCount; $j++) {
							$result .= $indentStr;
						}
					}
					$result .= $char;
				}
				elseif(($char=='{' || $char=='[')){
					if ($prevChar != ':'){
						$result .= $newLine;
						for($j = 0; $j < $indentCount; $j++) {
							$result .= $indentStr;
						}
					}
					$result .= $char;
					$indentCount = $indentCount + 1;
				}
				elseif(($char=='}' || $char==']')){
					$indentCount = $indentCount - 1;
					$result .= $newLine;
					for($j = 0; $j < $indentCount; $j++) {
						$result .= $indentStr;
					}
					$result .= $char;
				}
				else{
					$result .= $char;
				}
			}
			$prevChar = $char;
		}
		return $result;
	}
	
	public static function unicodeEncode($str, bool $htmlMode = false){
		$prefix = "\\u";
		if ($htmlMode)
			$prefix = "&#";
		// split word
		preg_match_all('/./u', $str, $matches);
	 
		$unicodeStr = "";
		foreach($matches[0] as $m){
			$unicodeStr .= $prefix.base_convert(bin2hex(iconv('UTF-8', "UCS-4", $m)), 16, 10);
		}
		return $unicodeStr;
	}

    public static function unicodeDecode($unicode_str){
        $unicode_str = str_replace('"', '\"', $unicode_str);
        $unicode_str = str_replace("'", "\'", $unicode_str);
        $json = '{"str":"'.$unicode_str.'"}';
        $arr = json_decode($json, true);
        if(empty($arr)){
            return '';
        }
        return $arr['str'];
	}

	public static function getRelative($file, $relative) {
		$file = str_replace("\\", "/", $file); 
		$relative = str_replace("\\", "/", $relative); 
		return str_replace($relative, "", $file);
	}

    public static function getProtocol() {
        return (!empty($_SERVER["HTTPS"] ?? "") && $_SERVER["HTTPS"] != "off") ? "https" : "http";
    }
    
	public static function getDomain() {
		return $_SERVER["HTTP_HOST"] . ($_SERVER["SERVER_PORT"] == 80 ? '' : ':' . $_SERVER["SERVER_PORT"]);
	}

	public static function getQuery() {
		$ret = [];
		parse_str($_SERVER["QUERY_STRING"], $ret);
		return $ret;
	}

	public static function buildQuery($arr) {
		return http_build_query($arr);
	}

	public static function destory(&$var) {
		if (is_array($var)) {
			foreach ($var as $k => $v) {
				unset($var[$k]);
			}
			array_slice($var, 0, null, true);
		}
	}
}
?>