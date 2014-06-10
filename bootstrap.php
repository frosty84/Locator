<?php

/**
* Autoloading function
* 
* @param string $pClassName
*/
function my_autoload ($pClassName) {
    include("./classes/" . $pClassName . ".class.php");
}

spl_autoload_register("my_autoload");

#additional functions
require_once "./functions.php";