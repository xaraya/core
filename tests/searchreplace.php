<?php

$GLOBALS['called'] = false;

function replace( $matches )
{
    $GLOBALS['called'] = true;
    $list1 = explode(',', $matches[1]);
    $list2 = explode(',', $matches[2]);
    
    $max1 = 0;
    $max2 = 0;

    foreach ($list1 as $key => $value) {
        $list1[$key] = trim($list1[$key]);
        if (strlen($list1[$key]) > $max1) $max1 = strlen($list1[$key]);
    }

    foreach ($list2 as $key => $value) {
        $list2[$key] = trim($list2[$key]);
        if (strlen($list2[$key]) > $max2) $max2 = strlen($list2[$key]);
    }
    
    $text = '';
    foreach ($list1 as $key => $value) {
        $localmax1 = $max1-strlen($list1[$key]) + 1;
        $localmax2 = $max2-strlen($list2[$key]) + 1;

        $text .= "if(!xarVarFetch(${list2[$key]},";
        for ($i=0;$i<$localmax2;$i++) $text.=' ';
        $text .= "'isset', ${list1[$key]},";
        for ($i=0;$i<$localmax1;$i++) $text.=' ';
        $text .= " , XARVAR_NOT_REQUIRED)) {return;}\r\n";
    }
    return $text;
//    return array ($matches[0] => $text);
}

function searchDir($path) {

    $fileModules = array();
    $dh = opendir($path);

    while ($entry = readdir($dh)) {
        if (is_dir("$path/$entry")) {
            if (($entry != '.') &&
                ($entry != '..') &&
                ($entry != 'CVS') &&
                ($entry != 'SCCS') &&
                ($entry != 'PENDING')) {
                //Recurse
                searchDir("$path/$entry");
            }
        } else { // -> is file
            if (substr($entry, strlen($entry)-4) == '.php') {
                echo "Reading file $path/$entry\r\n";
                $file = file ("$path/$entry");

                $file_contents = implode ('', $file);
                
                //This is to know if something was replace or not
                $GLOBALS['called'] = false;
                //"#list\((.*)\)[^=;]*=[^x;]*xarVarCleanFromInput\([(.*)[,\r\n]*]*\);#mU"

                $new_file_contents = preg_replace_callback( "|list[ ]*\(([^)]*)\)[ ]*=[ ]*xarVarCleanFromInput[ ]*\(([^)]*)\)[ ]*;|mU", 'replace', $file_contents );

                if ($GLOBALS['called']) {
                    $fp = fopen ( "$path/$entry.test", "w+" );
                    fwrite($fp, $new_file_contents);
                    fclose($fp);

                    //Something was replaced!
                    //Exchange Contents
                }
            }
        }
    }

    closedir($dh);
}

$path = '../html';
searchDir ('.');

?>
