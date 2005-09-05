#!/usr/local/bin/php
<?php

/**
 * File: $Id$
 *
 * fun little utility module for slicing up oldstyle xaraya function files into 
 * new-style directory type/function.php files
 * REQUIREMENT: EVERY function must have a PHPDOC Tag style comment like this one
 * that starts with /**
 *
 * @package tests
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @author Carl P. Corliss <rabbitt@xaraya.com>
*/

/**
 * Entry function
 *
 * @author Carl P. Corliss
 * @access private
 * @returns integer     0 on success, -1 on error
 */

function main ( ) {
    
    if (count($GLOBALS['argv']) <= 2 || count($GLOBALS['argv']) >= 10) {
        usage();
        return 0;
    } else {
        $options = getopt("f:m:t:Oh");
        
        if (isset($options['h']) || !isset($options['f']) || !isset($options['m']) || !isset($options['t'])) {
            usage();
            return 0;
        }
        $file = $options['f'];
        $module = $options['m'];
        $type = $options['t'];
        
        if (isset($options['O'])) {
            $GLOBALS['force_write'] = TRUE;
        }
        
        if (!is_file($file)) {
            echo "File [$file] does not exist!\n";
            return -1;
        }
    }
    
    $lines = file($file);
    
    
    $fileName     = basename($file);
    $dirLocation  = dirname($file);
    $aFile = explode('.',$fileName);
    $dirName = $aFile[0];
    
    $directory = "$dirLocation/$dirName";
    
    if (!is_dir($directory)) {
        if (!mkdir($directory)) {
            echo "\nCould not create directory to place files --> [$directory].
                    Please check your access permissions on the parent directory.\n";
        }
    }

    write_functions(grab_functions($lines, $module, $type), $lines, $directory); 
    
    return 0;
}

/**
 * Display how to use this app and exit.
 *
 * @author Carl P. Corliss
 * @access private
 * @returns void
 */

function usage() {
    
    echo "\nTakes a file and parses out each function into it's own seperate file";
    echo "\nfirst, however, it creates a directory based off the name of the file, to store the functions in\n";
    echo "\nUsage: {$GLOBALS['argv'][0]} -f <file> -m <module> -t <type> -O -h";
    echo "\n       -f   - the file you want to split";
    echo "\n       -m   - the module this file belongs to";
    echo "\n       -t   - the type of function library this is (user|userapi|admin|adminpi|other|otherapi|etc)";
    echo "\n       -O   - Force the writing of the files whether they exist already or not (NOTE: will overwrite any pre-existing files)";
    echo "\n       -h   - Help -- this usage message\n\n";
    
    return;
}

/**
 * Entry function
 *
 * @author Carl P. Corliss
 * @access private
 * @param array $lines the lines, in an array, from the file
 * @param string $module the name of the module this function set belongs to
 * @param string $type the type of function library this is (user|userapi|admin|adminapi|etc)
 * @returns array a mapping of functions and their line numbers 
 */

function grab_functions($lines, $module, $type) {
    
    foreach ($lines as $num => $text) {
        if (eregi("function([ ]*)?{$module}_{$type}_([a-zA-Z0-9_]*)([ ]*)?\(",$text, $regs)) {
            
            $i = $num - 1;
            while (!ereg("([ ]*)?\/\*\*",$lines[$i]) && $i > 0) {
                $i--;
            }
            $funcList[$regs[2]]['start'] = $i;
            
            if (isset($prevFunc)) {
                $funcList[$prevFunc]['end'] = $i - 1;
            } 
            
            $prevFunc = $regs[2];
        } elseif (eregi("^\?\>",$text)) {
            $funcList[$prevFunc]['end'] = $num - 1;
        }
        
    }
    
    return $funcList;
}


/**
 * Entry function
 *
 * @author Carl P. Corliss
 * @access private
 * @param array $funcMap mapping of functions to their line numbers (start/end) 
 * @param array $file an array containing all the lines of the file
 * @param string $directory the directory these files will be placed in
 * @returns void
 */

function write_functions($funcMap, $file, $directory) {
    
    foreach ($funcMap as $funcName => $func) {
        $funcFileName = $directory . "/" . $funcName . ".php";
        
        if (isset($GLOBALS['force_write']) && $GLOBALS['force_write'] == TRUE) {
            $fd = fopen($funcFileName, "w");
        } else {
            if (is_file($funcFileName)) {
                echo "\nFile for function [$funcName] already exists - skipping...";
                continue;
            } else {
                $fd = fopen($funcFileName, "w");
            }
        }
        
        echo "\nWriting function file: [$funcFileName]";
           
        // add < ? php as first line
        fwrite($fd, "<?php\n\n", 7);
        
        for ($i = $func['start']; $i <= $func['end']; $i++) {
            fwrite($fd, $file[$i], strlen($file[$i]));
        }
        
        // add closing ? > tag
        fwrite($fd, "?>", 2);
        
        fclose($fd);
    }
            
}

main();

?>