<?php
    
  /**
  * Function to handle DOMDocument::loadXML errors
  *   
  * @param mixed $errno
  * @param mixed $errstr
  * @param mixed $errfile
  * @param mixed $errline
  */
  function HandleXmlError($errno, $errstr, $errfile, $errline){
    if ($errno == E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()") > 0)){
        throw new DOMException($errstr);
    }
    else 
        return false;
  }
