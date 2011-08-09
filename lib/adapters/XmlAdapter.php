<?php

/**
 * XML Adapter Class
 * Its job is to handle XML code and create an array of
 * code and comments from a XML file.
 * @author Kasper Garnæs
 **/

class XmlAdapter {

  /**
   * Main parsing method.
   * Uses the php tokenizer to separate comments from code
   * @return `array`
   *    array("code"=>`array of code lines`, "docs"=>`array of doc blocks`)
   **/
  public function parse($file) {
    // Load the file
    $file = trim(file_get_contents($file));
    // Determine if we start with a documentation or code block
    $is_start_doc = '<!--' == substr($file, 0, 4);
    // Explode by start comment delimiter.
    // If we start with a comment block every array entry will contain
    // documentation first followed by code. Otherwise we start with a entry
    // containing nothing but code followed by documentation-code entries.
    $file = explode('<!--', $file);
    // Explode every entry by end comment delimiter.
    foreach ($file as &$f) {
      $f = explode('-->', $f);
    }
    // Every entry should now be an array containing documentation first and
    // code second. If we started with a code only block then we add an empty
    // documentation block to keep this structure.
    if (!$is_start_doc) {
      array_unshift($file[0], '');
    }

    // Separate entries into code and documentation
    $docs = array();
    $code = array();
    for ($i = 0; $i < sizeof($file); $i++) {
      $docs[] = trim($file[$i][0]);
      $code[] = $file[$i][1];
    }

    // Passes code onto pygmentize to add syntax highlighting
    // Assemble the code into a single string we can pass on to pygmentize.
    // Each block is separated by a delimiter.
    $code = implode("\n<!--CODEBLOCK-->\n", $code);
    $pyg = new Pygment;
    $code = $pyg->pygmentize("xml", $code);
    // Separate by syntax highlighted delimiter again
    $code = explode('<span class="c">&lt;!--CODEBLOCK--&gt;</span>', $code);

    foreach($code as &$val) {
      $val = rtrim($val);
      $val = str_replace("\t", " ", $val);
      $val='<div class="highlight"><pre>'.trim($val, "\n\r").'</pre></div>';
    }

    // Pass documentation through Markdown
    foreach($docs as &$doc) {
      $doc = Markdown($doc);
    }

    // Our final array of code mapped to comments
    return array("code"=>$code,"docs"=>$docs);
  }

}
