<?php
require_once("lib/markdown.php");
require_once("lib/pygment.php");

class Phrocco {

  public $sources;
  public $sections = array();
  public $title;
  public $file;
  public $adapter;
  public $output_file;
  public $path;


  public function __construct($language, $file) {
    $this->adapter = ucfirst($language)."Adapter";
    require_once(dirname(__FILE__)."/lib/adapters/".$this->adapter.".php");
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
    $view_file = dirname(__FILE__)."/lib/template/layout.html";
    $this->style = file_get_contents(dirname(__FILE__)."/lib/template/layout.css");
    extract((array)$this);
  	if(!is_readable($view_file)) throw new Exception("Unable to find Template File");
  	if(!include($view_file)) throw new Exception("PHP Error in $view_file");
  	$content = ob_get_clean();
		file_put_contents($this->output_file, $content);
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
    "i"   => null,
    "l"   => "php",
    "o"   => false
  );
  public $options = array();
  public $group = array();

  public function __construct($options) {
    $this->default['i'] = dirname(__FILE__);
    $sources = array();
    $this->options = $options + $this->defaults;
    $dir_iterator = new PhroccoIterator($this->options["i"]);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if(!$iterator->isDot() && in_array($iterator->getExtension(), $this->extensions[$this->options["l"]])) {
        $base_path = $this->options["i"];
        $rpath = str_replace($base_path, "",$file->getPath());
        $phrocco = new Phrocco($this->options["l"], $file);
        if(!$this->options["o"]) $output_dir = $file->getPath();
        else $output_dir = $this->options["o"];
        if($rpath !=$file->getPath()) $output_dir.="/".$rpath;
        if(!is_writable($output_dir)) @mkdir($output_dir, 0777, true);
        if(!is_writable($output_dir)) throw new Exception("Invalid Output Directory - Couldn't Create Because of Permissions");
        $file_out = $output_dir."/".$file->getBasename($iterator->getExtension())."html";
        $phrocco->output_file = $file_out;
        $phrocco->path = "./".$iterator->getSubPath();
        $this->group[$file->getBasename()] = $phrocco;
        $this->sources[] = array("url"=>$iterator->getSubPath()."/".$file->getBasename($iterator->getExtension())."html", "name"=>$file->getBasename());
      }
    }
    foreach($this->group as $name=>$file) {
      $file->sources = $this->sources;
      echo "*** Processing: ".$name."\n";

      $file->render();
    }

  }
}
