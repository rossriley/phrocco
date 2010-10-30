<?php

class PhpAdapter {
  
  public $comment_expr = "#//(?-ms:(.*))|(?:/\*+)(.*?)(?:\*+/)#ms";
  public $multi_block = "#(\n\s+)\*#ms";
  
  public function parse($file) {
    $sections = array();
    $file = file_get_contents($file);
    $code = preg_replace($this->comment_expr,"\n//CODEBLOCK\n", $file);
    $pyg = new Pygment;
    $code = $pyg->pygmentize("php",$code);
    $code = explode('<span class="c1">//CODEBLOCK</span>', $code);
    foreach($code as &$val) $val='<div class="highlight"><pre>'.$val.'</pre></div>'; 
    preg_match_all($this->comment_expr,$file,$matches);
    $matches = preg_replace($this->multi_block, "$1", $matches[2]);    
    $docs[]="";
    foreach($matches as $match) {
      $docs[] = Markdown($match);
    }
    return array("code"=>$code,"docs"=>$docs);
  }
  
}