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

                foreach ($file as $line) {
                    if (strpos($line, "xarVarFetch") !== false) {
                        echo  "->  $line";
                    }
                }

                echo "\r\n";
            }
        }
    }

    closedir($dh);
}

$path = '../html';
searchDir ($path);

?>