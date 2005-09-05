<?php

$GLOBALS['variables_found'] = array();
$GLOBALS['called'] = false;

set_time_limit(900);

function first_replace ( $matches )
{
    $match = $matches[2];

    $exploded = explode('"', $match);

    $size = count($exploded);

    for ($i=0; $i<$size; $i++) {
        if (!($i % 2)) {
            //Inside quotes
            $exploded[$i] = preg_replace_callback("/\\\$[^ '\\n\\r]*/m", 'replace_inside_quotes', $exploded[$i] );
        } else {
            //Outside quotes
            $exploded[$i] = preg_replace_callback("/[ ]*\\.[ ]*([^\\.]*)[ ]*\\.[ ]*/m", 'replace_outside_quotes', $exploded[$i] );
        }
    }

    $matches[3] = preg_replace_callback("/\\\$query/m", 'replace_result', $matches[3] );

    $result_string = $matches[1] . implode('', $exploded) . $matches[3];

//    print_r($matches);
//    print_r ($result_string);

    //Clean everything...
    //Seems the regex in the main function is catching more than i expected, so this quick
    //hack will make it work without having to delve into that again
    $GLOBALS['variables_found'] = array();
    $GLOBALS['called'] = true;

    return $result_string;
}

function replace_result ( $matches ) {
    array_unshift($GLOBALS['variables_found'], '$query');

    return implode(', ', $GLOBALS['variables_found']);
}



function replace_inside_quotes ( $matches ) {
    $GLOBALS['variables_found'][] = $matches[0];

    return '?';
}

function replace_outside_quotes ( $matches ) {
    $string = preg_replace('/xarVarPrepForStore\\((.*)\\)/', "$1", $matches[1]);

    $GLOBALS['variables_found'][] = $string;

    return '?';
/*
    $file_contents = preg_replace_callback(
      "//m", 'replace_query', $match );
*/
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

                //This is to know if something was replaced or not
                $GLOBALS['variables_found'] = array();

                $new_file_contents = preg_replace_callback(
                     "/(\\\$query[ ]*=[ ]*\")([^;]*)(\";[^;]*\\\$result[^-]*-\\>Execute[^;]*;)/m", 'first_replace', $file_contents );

                if ($GLOBALS['called']) {
                    echo "Changing file $path/$entry\r\n";
                    $fp = fopen ( "$path/$entry", "w+" );
                    fwrite($fp, $new_file_contents);
                    fclose($fp);

                    $GLOBALS['called'] = false;
                    //Something was replaced!
                    //Exchange Contents
                }
            }
        }
    }

    closedir($dh);
}

echo "<PRE>";
$path = './../../html';
searchDir ($path);
echo "</PRE>";

?>