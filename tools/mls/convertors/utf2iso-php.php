<?php
// DIRECTORY - /var/locales
// RUN - http://YOURDOMAIN/var/locales/utf2iso-php.php

$inputLocale = "ru_RU.utf-8";
$outputLocale = "ru_RU.windows-1251";
$inputEncoding = "utf-8";
$outputEncoding = "windows-1251";

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
            if (substr($entry, strlen($entry)-4) == '.php') {
                echo "<br>Reading file $path/$entry\r\n";
                $file = file ("$path/$entry");
                $file_contents = implode ('', $file);
                $new_file_contents2 = iconv($inputEncoding, $outputEncoding, $file_contents );
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
$path = $inputLocale.'/php';
searchDir ($path);
?>