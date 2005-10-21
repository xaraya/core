<?php

/**
 *  Helper function for variable logging
 *
 */
function xarLog__dumpVariable ($array)
{

    static $depth = 0;

    // $var, $name, $classname and $format
    extract($array);

    if ($depth > 32) {
        return 'Recursive Depth Exceeded';
    }
    
    if ($depth == 0) {
        $blank = '';
    } else {
        $blank = str_repeat(' ', $depth);
    }
    $depth += 1;
    
    $TYPE_COLOR = "#FF0000";
    $NAME_COLOR = "#0000FF";
    $VALUE_COLOR = "#999900";
    
    $str = '';
    
    if (isset($name)) {
        if ($format == 'html') {
            $str = "<span style=\"color: $NAME_COLOR;\">".$blank.'Variable name: <b>'.
                htmlspecialchars($name).'</b></span><br/>';
        } else {
            $str = $blank."Variable name: $name\n";
        }
    }
    
    $type = gettype($var);
    if (is_object($var)) {
        $args = array('name'=>$name, 'var'=>get_object_vars($var), 'classname'=>get_class($var), 'format'=>$format);
        // RECURSIVE CALL
        $str = xarLog__dumpVariable($args);
    } elseif (is_array($var)) {
        
        if (isset($classname)) {
            $type = 'class';
        } else {
            $type = 'array';
        }
        
        if ($format == 'html') {
            $str .= "<span style=\"color: $TYPE_COLOR;\">".$blank."Variable type: $type</span><br/>";
        } else {
            $str .= $blank."Variable type: $type\n";
        }
        
        if ($format == 'html') {
            $str .= '{<br/><ul>';
        } else {
            $str .= $blank."{\n";
        }
        
        foreach($var as $key => $val) {
            $args = array('name'=>$key, 'var'=>$val, 'format'=>$format);
            // RECURSIVE CALL
            $str .= xarLog__dumpVariable($args);
        }
        
        if ($format == 'html') {
            $str .= '</ul>}<br/><br/>';
        } else {
            $str .= $blank."}\n\n";
        }
    } else {
        if ($var === NULL) {
            $var = 'NULL';
        } else if ($var === false) {
            $var = 'false';
        } else if ($var === true) {
            $var = 'true';
        }
        if ($format == 'html') {
            $str .= "<span style=\"color: $TYPE_COLOR;\">".$blank."Variable type: $type</span><br/>";
            $str .= "<span style=\"color: $VALUE_COLOR;\">".$blank.'Variable value: "'.
                htmlspecialchars($var).'"</span><br/><br/>';
        } else {
            $str .= $blank."Variable type: $type\n";
            $str .= $blank."Variable value: \"$var\"\n\n";
        }
    }
    
    $depth -= 1;
    return $str;
}

?>