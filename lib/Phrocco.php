<?php
namespace Phrocco;

/**
 *###Phrocco main manager class.
 *
 * It works on a single language file, delegating the processing to an adapter class,
 * then writes the resulting parsed content to an output file using the built-in templates.
 *
 * @author Ross Riley
 **/

class Phrocco
{

    /**
     *####Class Variables
     * Sections array is the main storage for code mapped to documentation.
     *
     * @var `array`
     **/
    public $sections = array();

    /**
     * Gets passed into the template file to become the HTML page title.
     *
     * @var `string`
     **/
    public $title;

    /**
     * The file that we will operate on.
     *
     * @var `string`
     **/
    public $file;

    /**
     * This is primarily for internal use, storing a reference to the adapter class that will handle conversion.
     *
     * @var `Object`
     **/
    public $adapter;

    /**
     * The output file that the final html doc file will be written to.
     * @var `string`
     **/
    public $output_file;

    /**
     *#### Custom template variables
     *
     * Pass in standalone template files / css files to change the way the documentation looks.
     *
     * @var `string`
     **/
    public $layoutFile = false;
    public $stylesheetFile = false;

    /**
     *###Class Constructor
     * @param `string` $language Defaults to PHP
     *
     * @param `string` $file The language file to parse
     *
     * @param `array`  $options Pass in a custom title, template or stylesheet
     *
     * eg: `$phrocco = new Phrocco("php","test.php", ["title"=>"Example Doc File"]);`
     *
     * @return `void`
     **/

    public function __construct($language, $file, $options = array())
    {
        $classname = "Phrocco\\Adapter\\".ucfirst($language)."Adapter";
        $this->adapter = new $classname;
        $this->file = $file;
        if(isset($options["title"])) {
            $this->title = $options["title"];
        } else $this->title = basename($this->file);

        if(isset($options["template"])) {
            $this->layoutFile = $options["template"];
        } else $this->layoutFile = __DIR__."/template/layout.html";

        if(isset($options["stylesheet"])) {
            $this->stylesheetFile = $options["stylesheet"];
        } else $this->stylesheetFile = __DIR__."/template/layout.css";

    }


    /**
     * Proxies the parsing responsibility to the selected adapter.
     *
     * @return `void`
     **/

    public function parse()
    {
        $this->sections = $this->adapter->parse($this->file);
    }



    /**
     *### Template Write method
     * By this point the sections array will be populated with either side of the Comment / Code divide.
     * This method includes the template file and writes the final buffer to the output file.
     *
     * @return `void`
     **/
    public function write($content)
    {
        $result = file_put_contents($this->output_file, $content);
        if(false === $result) throw new \Exception("Unable to write final output to $this->output_file. Check permissions and try again", 1);
        return true;
    }

    /**
     *### Template Render
     * By this point the sections array will be populated with either side of the Comment / Code divide.
     * This method includes the template file and returns the generated content ready to write to file.
     *
     * @return `string` $output
     **/
    public function render()
    {
        ob_start();
        $this->parse();

        $this->style = file_get_contents($this->stylesheetFile);
        extract((array)$this);

        if(!is_readable($this->layoutFile)) {
            throw new \Exception("Unable to find Template File");
        }

        if(!include($this->layoutFile)) {
            throw new \Exception("PHP Error in $this->layoutFile");
        }
        $content = ob_get_clean();
        return $content;
    }


}



