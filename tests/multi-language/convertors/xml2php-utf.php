<?php
// DIRECTORY - /var/locales
// RUN - http://YOURDOMAIN/var/locales/xml2php-utf.php

$inputLocale = "ru_RU.utf-8";

set_time_limit(360);
function searchDir($path)
{
    $fileModules = array();
    $dh = opendir($path);
    while ($entry = readdir($dh)) {
        if (is_dir("$path/$entry")) {
            if (($entry != '.') &&
                ($entry != '..')) {
                //Recurse
                $outpath = str_replace($inputLocale.'/xml', $inputLocale.'/php', "$path/$entry" );
                if (!file_exists($outpath)) {
                        mkdir($outpath);
                }
                searchDir("$path/$entry");
            }
        } else { // -> is file
            if (substr($entry, strlen($entry)-4) == '.xml') {
                echo "<br>Reading file $path/$entry\r\n";
                $xml_parser = xml_parser_create();
                if (!($fp = fopen("$path/$entry", "r"))) {
                        die("could not open XML input");
                }
                $data = fread($fp, filesize("$path/$entry"));
                fclose($fp);
                xml_parse_into_struct($xml_parser, $data, $vals, $index);
                xml_parser_free($xml_parser);
                $outpath = str_replace($inputLocale.'/xml', $inputLocale.'/php',    $path );
                if (!file_exists($outpath)) {
                        mkdir($outpath);
                }

                $entry2 = str_replace('.xml',    '.php',    $entry );
                $fp = fopen ( "$outpath/$entry2", "w+" );

                fputs($fp, '<?php'."\n");
                fputs($fp, 'global $xarML_PHPBackend_entries;'."\n");
                fputs($fp, 'global $xarML_PHPBackend_keyEntries;'."\n");
                foreach ($vals as $node) {
                        if ($node['tag'] == 'STRING') {
                                fputs($fp, '$xarML_PHPBackend_entries[\''.addslashes($node['value'])."']");
                        } elseif ($node['tag'] == 'KEY') {
                                fputs($fp, '$xarML_PHPBackend_keyEntries[\''.addslashes($node['value'])."']");
                        } elseif ($node['tag'] == 'TRANSLATION') {
                                fputs($fp, " = '".addslashes($node['value'])."';\n");
                        } 
                }
                fputs($fp, "?>");

                fclose($fp);
            }
        }
    }
    closedir($dh);
}
$path = $inputLocale.'/xml';
searchDir ($path);
?>