<?php
namespace Phrocco\Adapter;

use Michelf\Markdown;
use Phrocco\Pygment;

/**
 *###PHP Adapter Class
 * Its job is to handle php code and create an array of
 * code and comments from a php code file.
 *
 * @author Ross Riley
 **/

class PhpAdapter implements AdapterInterface
{
    // Regular Expression to find strip out all type of comments characters
    public $comment_blocks = array(
        "#/\*+\s#ms",             // Multi Start
        "#\*+/#ms",               // Multi Block End
        "#/\*+([^\n]*)\*+/#ms",   // Multi Block on one line
        "#\s*\*([^\n]*\n)#ms",    // Multi Block
        "#//([^\n]*)#ms"          // Single Comment Line
    );


    public $comment_expr  = "#//([^\n]*)|(?:/\*+)(.*?)(?:\*+/)#ms";

    public $multi_block   = "#\s*\*([^\n]*)#ms";

    public $doc_params    = "#\s(@[^\s]*)#";

    public $comment_tokens = array("T_COMMENT", "T_DOC_COMMENT");

  /**
   *###Main parsing method.
   * Uses the php tokenizer to separate comments from code
   *
   * @return `array`
   *
   * `array("code"=>array of code, "docs"=>array of docs)`
   *
   **/
    public function parse($file)
    {
        $file = file_get_contents($file);
        $code = "";
        $all_matches = array();


       /*  This is the main parsing technique. It splits a php file into two arrays of comments
        *  and corresponding code. To do this is uses the PHP tokenizer to analyze the file
        *  for comments.
        */
        foreach(token_get_all($file) as $tok) {
            if(is_array($tok)) {
                if(in_array(token_name($tok[0]), $this->comment_tokens)) {
                    if(isset($prev_tok[0]) && token_name($prev_tok[0])=="T_COMMENT" && token_name($tok[0])=="T_COMMENT") {
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
        $docs = array();
        foreach($all_matches as $match) {
            $match = preg_replace($this->doc_params, "<em class='docparam'>$1</em>", $match);
            $docs[] = Markdown::defaultTransform($match);
        }

        // Our final array of code mapped to comments
        return array("code"=>$code,"docs"=>$docs);
    }

}
