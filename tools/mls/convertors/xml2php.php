<?php
// COMMAND LINE
// DIRECTORY - Run from any
// RUN - php xml2php.php <language> <locale> <encoding>
// TODO WEB
// TODO DIRECTORY - /var/locales
// TODO RUN - http://YOURDOMAIN/var/locales/xml2php.php

if ($argc != 4) {
    echo "This is a command line PHP script for translations files generation.
    Usage: $argv[0] <language> <locale> <encoding>
    Examples
    $argv[0] fr fr_FR utf-8
    $argv[0] fr fr_FR iso-8859-1
    $argv[0] ru ru_RU utf-8
    $argv[0] ru ru_RU windows-1251\n"; 
    exit;
} else {
    $language = $argv[1];
    $locale = $argv[2];
    $encoding = $argv[3];
}

$xmlpath = '/var/bk/xaraya/languages/stable/'.$language.'/';
$phppath = '/tmp/'.$language.'/';

$outputEncoding = $encoding;
$inputEncoding = 'utf-8';
$inputLocale   = $locale . '.' . $inputEncoding;
$outputLocale  = $locale . '.' . $outputEncoding;

set_time_limit(360);

function mkdirr($path, $mode = 0777)
{
    // Check if directory already exists
    if (is_dir($path) || empty($path)) {
        return true;
    }

    // Crawl up the directory tree
    $next_path = substr($path, 0, strrpos($path, '/'));
    if (mkdirr($next_path, $mode)) {
        if (!file_exists($path)) {
            return mkdir($path, $mode);
        }
    }
    return false;
}
                                                                    
function searchDir($path)
{
    global $inputLocale, $outputLocale;
    global $inputEncoding, $outputEncoding;
    global $xmlpath, $phppath;

    $fileModules = array();
    $dh = opendir($path);
    while ($entry = readdir($dh)) {
        if (is_dir("$path/$entry")) {
            if (($entry != '.') &&
                ($entry != '..') &&
                ($entry != 'SCCS')) {
                //Recurse
                $outpath = str_replace(
                    $xmlpath.$inputLocale.'/xml',
                    $phppath.$outputLocale.'/php',
                    "$path/$entry" );
                echo "Testing directory $outpath\r\n";
                if (!file_exists($outpath)) {
                    mkdirr($outpath);
                }
                searchDir("$path/$entry");
            }
        } else { // -> is file
            if (substr($entry, strlen($entry)-4) == '.xml') {
                echo "Reading file $path/$entry\r\n";
                $xml_parser = xml_parser_create();
                if (!($fp = fopen("$path/$entry", "r"))) {
                    die("could not open XML input");
                }
                $data = fread($fp, filesize("$path/$entry"));
                fclose($fp);
                xml_parse_into_struct($xml_parser, $data, $vals, $index);
                xml_parser_free($xml_parser);

                $outpath = str_replace(
                    $xmlpath.$inputLocale.'/xml',
                    $phppath.$outputLocale.'/php',
                    $path );
                if (!file_exists($outpath)) {
                    mkdirr($outpath);
                }

                $entry2 = str_replace('.xml',    '.php',    $entry );
                echo "Writing file $outpath/$entry2\r\n";
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
                        if ($outputEncoding != 'utf-8') {
                            $translation = iconv($inputEncoding, $outputEncoding, $node['value'] );
                        } else {
                            $translation = $node['value'];
                        }
                        fputs($fp, " = '".addslashes($translation)."';\n");
                    }
                }
                fputs($fp, "?>");

                fclose($fp);
            }
        }
    }
    closedir($dh);
}
$path = $xmlpath . $inputLocale.'/xml';
searchDir ($path);

?>