<?php
namespace Phrocco;

/**
 *###Phrocco Custom Iterator.
 *
 * Extends Recursive Directory Iterator to add an extra `getExtension()` method.
 *
 * @author Ross Riley
 **/
class PhroccoIterator extends \RecursiveDirectoryIterator
{

    public function getExtension() {
        $filename = $this->getFilename();
        $fileExtension = strrpos($filename, ".", 1) + 1;
        if ($fileExtension !== false) {
            return strtolower(substr($filename, $fileExtension, strlen($filename) - $fileExtension));
        } else {
            return "";
        }
    }


}