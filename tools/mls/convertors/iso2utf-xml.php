<?php
// DIRECTORY - /var/locales
// RUN - http://YOURDOMAIN/var/locales/iso2utf-xml.php

$inputLocale = "es_ES.iso-8859-1";
$outputLocale = "es_ES.utf-8";
$inputEncoding = "iso-8859-1";
$outputEncoding = "utf-8";

set_time_limit(360);
function searchDir($path)
{
    global $inputLocale, $outputLocale, $inputEncoding, $outputEncoding;

    $fileModules = array();
    $dh = opendir($path);
    while ($entry = readdir($dh)) {
        if (is_dir("$path/$entry")) {
            if (($entry != '.') &&
                ($entry != '..') &&
                ($entry != 'SCCS')) {
                //Recurse
                $outpath = str_replace($inputLocale,    $outputLocale,    "$path/$entry" );
                if (!file_exists($outpath)) {
                        mkdir($outpath);
                }
                searchDir("$path/$entry");
            }
        } else { // -> is file
            if (substr($entry, strlen($entry)-4) == '.xml') {
                echo "Reading file $path/$entry\r\n";
                $file = file ("$path/$entry");
                $file_contents = implode ('', $file);
                $new_file_contents1 = str_replace(
                     "locale=\"$inputLocale\"", 
                     "locale=\"$outputLocale\"", 
                     $file_contents );
                $new_file_contents2 = iconv($inputEncoding, $outputEncoding, $new_file_contents1 );
                //$new_file_contents2 = mb_convert_encoding($file_contents, $outputEncoding, $inputEncoding);
                $outpath = str_replace($inputLocale, $outputLocale,    $path );
                if (!file_exists($outpath)) {
                        mkdir($outpath);
                }
                $fp = fopen ( "$outpath/$entry", "w+" );
                fwrite($fp, $new_file_contents2);
                fclose($fp);
            }
        }
    }
    closedir($dh);
}
$path = $inputLocale.'/xml';
searchDir ($path);
?>