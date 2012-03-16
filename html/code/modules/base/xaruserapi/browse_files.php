<?php
/**
 * Browse for files and directories
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Browse for files and directories (recursion supported).
 * Originally in Xarpages, and used to provide lists of APIs and templates.
 *
 * @param array   $args array of optional parameters<br/>
 *
 * Identifying the base directory:<br/>
 *        string  $args['basedir'] the absolute or relative base directory<br/>
 *        string  $args['module'] the name of the module to look in (treated as optional root)<br/>
 *
 * Matching files and directories (filtering rules):<br/>
 *        string  $args['match_glob'] file glob expression<br/>
 *        string  $args['match_re'] regular expression<br/>
 *        string  $args['match_exact'] expression<br/>
 *        string  $args['is_writeable'] return only writable files and directories<br/>
 *        string  $args['is_readable'] return only readable files and directories<br/>
 *        array   $args['skipdirs'] list of directories to skip; '.' and '..' will always be added<br/>
 *        boolean $args['skipdirscc'] skip common configuration control directories<br/>
 *
 * Transform functions (modifying the filename to be returned):<br/>
 *        string  $args['strip_re'] regular expression matching details to strip out of the filename<br/>
 *
 * Other flags:<br/>
 *        integer $args['levels'] number of levels to recurse (default=max_levels)<br/>
 *        string  $args['etpath'] 'abs' will return the absolute OS path, 'rel' the relative path to the basedir, 'file' just the filename<br/>
 *        boolean $args['retdirs'] flag that indicates directories should be returned (default false)<br/>
 *        boolean $args['retfiles'] flag that indicates files should be returned (default true)<br/>
 *
 * @todo support sorting of the files (by name, by date, asc/desc, etc)
 * @todo support timestamp matching (older, younger, range)
 * @todo support other areas than the module 'home', e.g. module theme area
 * @todo support multi-theme searching, allowing images to be searched in many places
 * @todo support retpath value 'rel2' for path relative to the site entry point
 * @todo support retpath value 'api' to return 'API' forms of the path (module:type:func string)
 * @todo allow the returning of more detailed file information than just names - full inode info (Windows?)
 * @todo provide a transform function for the filename, probably a callback function, e.g. 'my_logo.gif' => 'My Logo'
 * @todo allow wildcards for modules and even for basedir, so the function will scan multiple modules or trees
 * @todo support case sensitive/insensitive flag
 * @todo support 'maxfiles' to limit the number of files that can be returned
 */

function base_userapi_browse_files(Array $args=array())
{
    extract($args);

    // Maximum possible directory levels the function will follow.
    $max_levels = 255;

    // Levels lies between 1 and max_levels.
    // Set levels=1 to stay in a single diectory.
    if (!xarVarValidate('int:1:'.$max_levels, $levels, true)) {$levels = $max_levels;}

    // The path return format is an unumerated type.
    if (!xarVarValidate('enum:abs:rel:file', $retpath, true)) {$retpath = 'file';}

    // An array of directories to skip.
    if (!xarVarValidate('list:string:1', $skipdirs, true)) {$skipdirs = array();}

    // Always skip current and parent directory.
    $skipdirs += array('.', '..');

    // Skip common configuration control directories
    if (!empty($skipdirscc)) {$skipdirs += array('SCCS', 'sccs', 'CVS', 'cvs');}

    // Other flags.
    if (!isset($retdirs)) {$retdirs = false;}
    if (!isset($retfiles)) {$retfiles = true;}
    if (!isset($is_writeable)) {$is_writeable = false;}
    if (!isset($is_readable)) {$is_readable = false;}

    // Get the root directory.
    $rootdir = '.';

    // If the module is set, then find its home.
    if (!empty($module)) {
        // Assume for now that we are looking only in the module home directory.
        $modinfo = xarMod::getInfo(xarMod::getRegID($module));
        if (!empty($modinfo)) {
            $rootdir = './modules/' . $modinfo['directory'];
        }
    }
    
    // Get the base directory.
    // A relative base directory will be added to the [non-empty] root directory.
    // An absolute base directory will override the root directory.
    if (!empty($basedir)) {
        $basedir = trim($basedir);
        // TODO: is this the only check we need to do?
        if (substr($basedir, 0, 1) != '/' && !empty($rootdir)) {
            // The basedir is a relative path.
            $basedir = $rootdir . '/' . $basedir;
        }
    } else {
        $basedir = $rootdir;
    }

    // Get the absolute basedir path.
    $basedir = realpath($basedir);
    if (empty($basedir)) {
        // The base directory does not exist.
        return;
    }

    // Now we have the absolute base pathname. Start the search.
    $filelist = array();
    $scandir = array();

    // Start the file scan on the base directory.
    array_push($scandir, array(1, ''));

    while (!empty($scandir)) {
        list($thislevel, $thisdir) = array_shift($scandir);
        if ($dh = @opendir($basedir . $thisdir)) {
            while(($filename = @readdir($dh)) !== false) {
                // Got a file or directory.
                $thisfile = $basedir . $thisdir . '/' . $filename;

                // Skip if we only want readable files.
                if ($is_readable && !is_readable($thisfile)) {continue;}

                if (is_file($thisfile)) {
                    // Go to the next file if we don't want to return files.
                    if (!$retfiles) {continue;}

                    // Skip this file if we only want writeable files and directories.
                    if ($is_writeable && !is_writeable($thisfile)) {continue;}

                    // Check the filtering rules.
                    if (!empty($match_glob)) {
                        // If the glob pattern includes a path, then compare the complete path.
                        if (strpos($match_glob, '/') === false) {
                            if (@fnmatch($match_glob, $filename) !== true) {continue;}
                        } else {
                            if (@fnmatch($match_glob, ltrim($thisdir . '/' . $filename, '/')) !== true) {continue;}
                        }
                    }
                    if (!empty($match_re) && @preg_match($match_preg, ltrim($thisdir . '/' . $filename, '/')) !== true) {continue;}
                    if (!empty($match_exact) && $match_exact !== $filename) {continue;}
                } elseif (is_dir($thisfile)) {
                    // Skip specified directories.
                    if (in_array($filename, $skipdirs)) {continue;}

                    // Skip this directory if we only want writeable files and directories.
                    if ($is_writeable && !is_writeable($thisfile)) {continue;}

                    if ($thislevel < $levels && is_readable($thisfile)) {
                        // We have not maxed out on the levels yet, so go deeper (only if dir is readable).
                        array_push($scandir, array($thislevel + 1, $thisdir . '/' . $filename));
                    }

                    // Go to the next file if we don't want to log the directory in the result set.
                    if (!$retdirs) {continue;}

                    // Suffix to indicate this is a directory.
                    $filename .= '/';
                } else {
                    // Neither a file nor directory.
                    continue;
                }

                // Strip out parts of the filename if necessary
                if (!empty($strip_re)) {
                    $filename = @preg_replace($strip_re, '', $filename);
                }
                
                // If we have got this far, then we have a file or directory to return.
                switch (strtolower($retpath)) {
                    case 'abs':
                        $filelist[] = $thisfile;
                        break;
                    case 'rel':
                        $filelist[] = ltrim($thisdir . '/' . $filename, '/');
                        break;
                    case 'file':
                        $filelist[] = $filename;
                        break;
                }
            }
            closedir($dh);
        }
    }

    return $filelist;
}

?>
