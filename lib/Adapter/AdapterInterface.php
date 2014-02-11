<?php
namespace Phrocco\Adapter;

/**
 *###Adapter Interface
 * Its job is to handle php code and create an array of
 * code and comments from a php code file.
 *
 * @author Vlad Bakin <mixkorshun@gmail.com>
 **/
interface AdapterInterface
{
    /**
     *###Main parsing method.
     *
     * @param `string` $file source file name for parsing
     *
     * @return `array` ["code"=>array of code, "docs"=>array of docs]
     */
    public function parse($file);
}
