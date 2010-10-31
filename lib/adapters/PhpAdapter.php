<?php

/**
 * PHP Adapter Class
 * Its job is to handle php code and create an array of
 * code and comments from a php code file.
 * @author Ross Riley
 **/

class PhpAdapter {
  // Regular Expression to find strip out all type of comments characters
  public $comment_blocks = array(
    "#/\*+\s#ms",             // Multi Start
    "#\*+/#ms",               // Multi Block End
    "#/\*+([^\n]*)\*+/#ms",   // Multi Block on one line
    "#\s*\*([^\n]*\n)#ms",      // Multi Block
    "#//([^\n]*)#ms"          // Single Comment Line
  );

  
  public $comment_expr  = "#//([^\n]*)|(?:/\*+)(.*?)(?:\*+/)#ms";
  public $multi_block   = "#\s*\*([^\n]*)#ms";
  public $doc_params    = "#\s(@[^\s]*)#"; 
  
  public $comment_tokens = array("T_COMMENT", "T_DOC_COMMENT");
  
  /**
   * Main parsing method.
   * Uses the php tokenizer to separate comments from code
   * @return `array` 
   *    array("code"=>`array of code lines`, "docs"=>`array of doc blocks`)
   *
   **/
  public function parse($file) {
    $fname = $file;
    $file = file_get_contents($file);
    $code = "";
    $all_matches = array();
    $prev_token = false;
    foreach(token_get_all($file) as $tok) {
      if(is_array($tok)) {
        if(in_array(token_name($tok[0]), $this->comment_tokens)) {
          if(token_name($prev_tok[0])=="T_COMMENT" && token_name($tok[0])=="T_COMMENT") {
            $last = array_pop($all_matches);
            $tok[1] = str_replace("\t","  ",$tok[1]);
            $all_matches[]=$last.preg_replace($this->comment_blocks,"$1", $tok[1]);
          } else {
            $tok[1] = str_replace("\t","  ",$tok[1]);
            $all_matches[]=preg_replace($this->comment_blocks,"$1", $tok[1]);
            $code.="\n//CODEBLOCK\n";
          }
        } else {
          $code.=$tok[1];
        }                  
      } else $code.=$tok;
      $prev_token = $tok;     
    }

    // Passes code onto pygmentize to add syntax highlighting
    $pyg = new Pygment;
    $code = $pyg->pygmentize("php",$code);
    $code = explode('<span class="c1">//CODEBLOCK</span>', $code);
    array_shift($code);
    foreach($code as &$val) {
      $val = rtrim($val);
      $val = str_replace("\t", " ", $val);
      $val='<div class="highlight"><pre>'.trim($val, "\n\r").'</pre></div>'; 
    }
    
    // Adds html markup to identify php docblock parameters
    foreach($all_matches as $match) {
      $match = preg_replace($this->doc_params, "<em class='docparam'>$1</em>", $match);
      $docs[] = Markdown($match);
    }
    // Our final array of code mapped to comments
    return array("code"=>$code,"docs"=>$docs);
  }
  
}
