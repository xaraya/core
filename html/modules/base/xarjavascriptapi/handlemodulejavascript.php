<?php

/**
 * Handle <xar:base-include-javascript ...> form field tags
 * Format : <xar:base-include-javascript definition="$definition" /> with $definition an array
 *       or <xar:articles-include-javascript filename="thisname.js" module="modulename" position="head|body|whatever"/>
 * Default module is 'base' and default position is 'head'; filename is mandatory.
 *
 * @author Jason Judge
 * @param $args array containing the form field definition or the type, name, value, ...
 * @returns string
 * @return empty string
 */ 
function base_javascriptapi_handlemodulejavascript($args)
{
    extract($args);

    // The whole lot can be passed in as an array.
    if (isset($definition) && is_array($definition)) {
        extract($definition);
    }

    // Set some defaults - only attribute 'filename' is mandatory.
    if (empty($module)) {$module = 'base';}
    if (empty($position)) {$position = 'head';}

    // Return the code to call up the javascript file.
    // Only the file version is supported for now.
    // If the tag that calls this function up is allowed to be open,
    // then its content value could be raw javascript for inclusion in
    // the head/body/whatever.
    if (!empty($filename)) {
        $out = "xarModAPIFunc("
            . "'base', 'javascript', 'modulefile', "
            ."array('module'=>'" . addslashes($module)
            . "', 'filename'=>'" . addslashes($filename)
            . "', 'position'=>'" . addslashes($position) . "')); ";
    } else {
        $out = '';
    }
 //return ' echo "'.$out.'"; ';
    return $out;
}

?>
