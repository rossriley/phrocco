#!/bin/sh
PHP=`which php`
exec $PHP -C -q -d output_buffering=1 "$0" "$@"
<?php
require_once("lib/markdown.php");
require_once("lib/pygment.php");


class Phrocco {

  public $sources;
  public $sections = array();
  public $title;
  public $file;
  public $adapter;

  
  public function __construct($language, $file) {
    $this->adapter = ucfirst($language)."Adapter";
    require_once(__DIR__."/lib/adapters/".$this->adapter.".php");
    $this->adapter = new $this->adapter;
    $this->file = $file;
    $this->title = basename($this->file);
  }
  
  public function parse() {
    $this->sections = $this->adapter->parse($this->file);
  }
  


  public function render() {
    ob_start();
    $this->parse();
    $view_file = __DIR__."/template/layout.html";
    $this->style = file_get_contents(__DIR__."/template/layout.css");
    extract((array)$this);
  	if(!is_readable($view_file)) throw new Exception("Unable to find Template File");
  	if(!include($view_file)) throw new Exception("PHP Error in $view_file");
  	$content = ob_get_contents();
		ob_end_clean();
  	return $content;
  }


}

class PhroccoIterator extends RecursiveDirectoryIterator {
    public function getExtension() {
        $Filename = $this->getFilename();
        $FileExtension = strrpos($Filename, ".", 1) + 1;
        if ($FileExtension != false)
            return strtolower(substr($Filename, $FileExtension, strlen($Filename) - $FileExtension));
        else
            return "";
    }
}

class PhroccoGroup {
  
  public $extensions = array(
    "php" => array("php","phps","phpt")
  );
  public $language = "php";
  public $defaults = array(
    "i"   => __DIR__,
    "l"   => "php",
    "o"   => false
  );
  public $options = array();
  
  public function __construct($options) {
    ini_set("error_reporting",247);
    $this->options = $options + $this->defaults;
    $dir_iterator = new PhroccoIterator($this->options["i"]);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if(!$iterator->isDot() && in_array($iterator->getExtension(), $this->extensions[$this->options["l"]])) {
        echo "*** Processing: ".$file->getBasename()."\n";
        $base_path = $this->options["i"];
        $rpath = str_replace($base_path, "",$file->getPath());
        $phrocco = new Phrocco($this->options["l"], $file);
        if(!$this->options["o"]) $output_dir = $file->getPath();
        else $output_dir = $this->options["o"];
        if($rpath !=$file->getPath()) $output_dir.="/".$rpath;
        if(!is_writable($output_dir)) @mkdir($output_dir, 0777, true);
        if(!is_writable($output_dir)) throw new Exception("Invalid Output Directory - Couldn't Create Because of Permissions");
        $file_out = $output_dir."/".$file->getBasename($iterator->getExtension())."html";
        file_put_contents($file_out, $phrocco->render());
      }
    }
  }
}
