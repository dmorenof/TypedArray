# Simple Typed Array

Simple abstract class for typed Arras.

## Installation

### Composer

To install the library, execute the following code:

    composer require typedarray/typedarray master

### Manual

Download the library from https://github.com/dmorenof/TypedArray

Include all the files from the "installation path/src"

    function autoload($path) {
        $items = glob($path . DIRECTORY_SEPARATOR . "*");
    
        foreach($items as $item) {
            $isPhp = pathinfo($item) ["extension"] === "php";

            if (is_file($item) && $isPhp) {
                require_once $item;
            } elseif (is_dir($item)) {
                autoload($item);
            }
        }
    }
    
    autoload($installation_path . DIRECTORY_SEPARATOR . 'src');
