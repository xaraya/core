<?php

set_time_limit(360);

function searchDir($path)
{

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

                $new_file_contents = preg_replace( "|([ ]*if[ ]*\([ ]*![ ]*xarVarFetch[ ]*\([^,]*,[ ]*'isset'[ ]*,[^,]*,[ ]*NULL[ ]*,[ ]*)(XARVAR_NOT_REQUIRED)([ ]*\)[ ]*\))|mU", "\$1XARVAR_DONT_SET\$3", $file_contents );

                if ($new_file_contents != $file_contents) {
                    echo "Changing file $path/$entry\r\n";
                    $fp = fopen ( "$path/$entry", "w+" );
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
searchDir ($path);

?>