<?php
/**
 * Dynamic data browse function
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * @param array    $args array of optional parameters<br/>
 */
function dynamicdata_adminapi_browse(Array $args=array())
{
    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    if (empty($args['basedir'])) throw new EmptyParameterException('basedir');
    if (empty($args['filetype'])) throw new EmptyParameterException('filetype');

    // Security check - we require OVERVIEW rights here for now...
    if(!xarSecurityCheck('ViewDynamicData')) return;

    // Get arguments from argument array
    extract($args);

    if (empty($filematch)) {
        $filematch = '';
    }
    if (!isset($recursive)) {
        $recursive = true;
    }

    $todo = array();
    $basedir = realpath($basedir);
    $filelist = array();
    array_push($todo, $basedir);
    while (count($todo) > 0) {
        $curdir = array_shift($todo);
        if ($dir = @opendir($curdir)) {
            while(($file = @readdir($dir)) !== false) {
                $curfile = $curdir . '/' . $file;
                if (preg_match("/$filematch\.$filetype$/",$file) && is_file($curfile)) {
                    // ugly fix for Windows boxes
                    $tmpdir = strtr($basedir,array('\\' => '\\\\'));
                    $curfile = preg_replace("#$tmpdir/#",'',$curfile);
                    $filelist[] = $curfile;
                } elseif ($file != '.' && $file != '..' && is_dir($curfile) && !empty($recursive)) {
                    array_push($todo, $curfile);
                }
            }
            closedir($dir);
        }
    }
    return $filelist;
}
?>