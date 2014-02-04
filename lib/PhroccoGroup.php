<?php
namespace Phrocco;

/**
 *###Phrocco Group processor class.
 *
 * Whereas the base Phrocco class operates on a single file to produce a single output,
 * This class looks after recursively iterating over a directory and creating an output for each file found.
 *
 * @author Ross Riley
 **/
class PhroccoGroup
{

    public $extensions = array(
        "php" => array("php","phps","phpt"),
        "xml" => array("xml")
    );

    /**
     *### Default options,
     * These are normally provided by the command-line tool but can be overridden if required.
     */
    public $language = "php";

    public $defaults = array(
        "i"   => __DIR__,
        "l"   => "php",
        "o"   => false
    );

    public $options = array();

    public $group = array();


    /**
     *###Constructor method,
     * Prepares class using command line options provided.
     *
     * @param array $options receives the command line options to decide where to choose files and output docs.
     **/
    public function __construct($options)
    {
        $sources = array();
        $this->options = $options + $this->defaults;
    }


    /**
     *## Iterate and Process
     * All the magic happens here, whils it looks complicated this is essentially a directory iterator
     * that creates an array of filenames, with a corresponding Phrocco object that can look after rendering itself.
     *
     **/
    public function process()
    {
        $dir_iterator = new PhroccoIterator($this->options["i"]);
        $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if(!$iterator->isDot() && in_array($iterator->getExtension(), $this->extensions[$this->options["l"]])) {

                $base_path = $this->options["i"];
                $rpath = str_replace($base_path, "",$file->getPath());
                $phrocco = new Phrocco($this->options["l"], $file);

                if(!$this->options["o"]) $output_dir = $file->getPath();
                else $output_dir = $this->options["o"];

                if($rpath !=$file->getPath()) $output_dir.="/".$rpath;


                /**
                 *### Check that we can write
                 * This block ensures that the output directory exists. If it doesn't we'll try and create it before giving up.
                 **/
                if(!is_writable($output_dir)) {
                    try {
                        mkdir($output_dir, 0777, true);
                    } catch (Exception $e) {
                        if(!is_writable($output_dir)) throw new \Exception("Invalid Output Directory - Couldn't Create Because of Permissions");
                    }
                }


                /**
                 *### Build the documentation tree
                 * This block builds the documentation file layout to mirror the code files scanned.
                 **/
                $file_out = $output_dir."/".$file->getBasename($iterator->getExtension())."html";
                $phrocco->output_file = $file_out;
                $subpath = $iterator->getSubPath();
                $phrocco->path = (!empty($subpath) ? "./" : '') . $subpath;
                $this->group[$file->getBasename()] = $phrocco;
                $subpath .= (!empty($subpath) ? '/' : '');

                $this->sources[] = array(
                    "url"=>$subpath.$file->getBasename($iterator->getExtension())."html",
                    "name"=>$file->getBasename(),
                    "level"=> $iterator->getDepth(),
                    "folder"=> $iterator->getSubPath()
                );
            }
        }




    }

    /**
     *### Final File renders
     * Iterates over all found files and calls on the Phrocco class to render each file.
     */
    public function write() {
        foreach($this->group as $name=>$file) {
            $file->sources = $this->sources;
            echo "*** Processing: ".$name."\n";
            $content = $file->render();
            $file->write($content);
        }
    }
}
