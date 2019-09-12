<?php
namespace com\appxml\util;

use com\appxml\exception\UnexpectedException;
use com\appxml\exception\FileNotExistException;
use com\appxml\util\Util;

/** TODO. Add cache for file */
class FileIO {
	public const OVERWRITE = "w+";
	public const READ = "r+";
	public const APPEND = "a+";
	
	//File
	private $file = null;
	private $mode;
	private $path;
	
	function __construct($path, $mode = "w+", $default = "") {
		$path = Util::urlFormat($path);
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0777 ,true);
		}
		if (!is_file($path)) {
			$this->file = fopen($path, "w+");
			$this->rewriteString($default);
		} else {
			$this->file = fopen($path, "r+");
		}
		$this->path = $path;
	}
	
	public function rewriteString($data) {
		$this->writeString($data, 0, true);
	}
	
	/**
	* Get file Lock for multiply users
	* #Bug Cannot fwrite("0", 0, true)
	*/
	public function writeString($data, $offset, $clear):void {
		if ($clear) {
			$this->clear();
		}
		if (empty($data))
			return;
		fseek($this->file, $offset);
		$starttime = microtime();
		do {
			$canWrite = flock($this->file, LOCK_EX);
			if (!$canWrite) usleep(round(rand(0, 100) * 1000));
		} while ((!$canWrite) && (microtime() - $starttime) < 1000);
		if ($canWrite) {
			if (!fwrite($this->file, $data))
				throw new UnexpectedException("Fails to write to file with ".$data);
			flock($this->file, LOCK_UN);
		}
	}
	/**
	* Get file Lock for multiply users
	*/
	public function readString():string {
		fseek($this->file, 0);
		$starttime = microtime();
		do {
			$canRead = flock($this->file, LOCK_SH);
			if (!$canRead) usleep(round(rand(0, 100) * 1000));
		} while ((!$canRead) && (microtime() - $starttime) < 1000);
		if ($canRead) {
			$filestr = "";
			while (!feof($this->file)) {
				$filestr .= fgets($this->file);
			}
		} else {
			throw new UnexpectedException("Fails to read to file with ".$data);
		}
		//Logger::dump($filestr,__FILE__,__LINE__,"filestr");
		return $filestr;
	}
	
	/**
	* CreatePath
	* @param string path
	*/
	public static function createPath($path):void {
		if (self::isDirectory($path)) {
			if (!is_dir($path)) {
				mkdir($path, 0777, true);
			}
			return;
		}
		$dir = dirname($path);
		if (!is_dir($dir)) {
			!mkdir($dir, 0777, true);
		}
		if (!is_file($path)) {
			$file = fopen($path, "w+");
			fclose($file);
		}
	}
	
	public static function delPath($path):void {
		if (self::isDirectory($path) && is_dir($path)) {
			$p = scandir($path);
			foreach ($p as $val) {
				if ($val != "." && $val != "..") {
					if (is_dir($path.$val)) {
						self::delPath($path.$val."/");
					} else {
						unlink($path.$val);
					}
				}
			}
			rmdir($path);
		} elseif (self::isFile($path) && is_file($path)) {
			unlink($path);
		} else {
			throw new FileNotExistException($path);
		}
	}
	
	/**
	 *  @brief MvPath
	 *  
	 *  @param [in] path a/b or a/b/
	 *  @param [in] newPath a/b or a/b/
	 */
	public static function mvPath($path, $newPath):void {
		//dir mode
		if (self::isDirectory($path) && self::isDirectory($newPath) && is_dir($path)) {
			if (!is_dir($newPath)) {
				mkdir($newPath, 0777, true);
			}
			$p = scandir($path);
			foreach ($p as $val) {
				if ($val != "." && $val != "..") {
					if (is_dir($path.$val."/")) {
						self::mvPath($path.$val."/", $newPath.$val."/");
					} else {
						rename($path.$val, $newPath.$val);
					}
				}
			}
			rmdir($path);
		} elseif (self::isFile($path) && self::isFile($newPath) && is_file($path)) {
			$dir = self::getDirectory($newPath);
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
			rename($path, $newPath);
		} else {
			throw new FileNotExistException($path);
		}
	}
	
	public function clear() {
		rewind($this->file);
		ftruncate($this->file, 0);
	}
	
	public function __destruct() {
		fclose($this->file);
		unset($this->file);
	}
	
	public function getPath():string {
		return $this->path;
	}
	
	public static function isDirectory($path) {
		return strrpos($path,"/") == strlen($path) - 1;
	}
	
	public static function isFile($path) {
		return strrpos($path,"/") != strlen($path) - 1;
	}
}
?>