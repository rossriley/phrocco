<?php

class PhpAdapter {
  
  public $comment_expr = "#//([^\n]*)|(?:/\*+)(.*?)(?:\*+/)#ms";
  public $multi_block = "#(\n\s+)\*#ms";
  
  public function parse($file) {
    $fname = $file;
    $file = file_get_contents($file);
    
    // Extract The Comments from the code
    preg_match_all($this->comment_expr,$file,$matches);

    $matches[2] = preg_replace($this->multi_block, "$1", $matches[2]);
    $all_matches = array();
    foreach($matches[0] as $key=>$mat) {
      if(strlen($matches[1][$key]) <1) $all_matches[$key] = $matches[2][$key];
      else $all_matches[$key] = $matches[1][$key];
    }
    
    
    $code = preg_replace($this->comment_expr,"\n//CODEBLOCK\n", $file);
    $pyg = new Pygment;
    $code = $pyg->pygmentize("php",$code);
    $code = explode('<span class="c1">//CODEBLOCK</span>', $code);
    array_shift($code);
    foreach($code as &$val) $val='<div class="highlight"><pre>'.$val.'</pre></div>'; 
       
    //$docs[]="";
    foreach($all_matches as $match) {
      $docs[] = Markdown($match);
    }
    //if(strpos($fname, "WaxEvent")) {print_r(array("code"=>$code,"docs"=>$docs)); exit;}
    return array("code"=>$code,"docs"=>$docs);
  }
  
}