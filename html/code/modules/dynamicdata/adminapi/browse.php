<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;
use EmptyParameterException;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata adminapi browse function
 * @extends MethodClass<AdminApi>
 */
class BrowseMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see AdminApi::browse()
     */
    public function __invoke(array $args = [])
    {
        // Argument check - make sure that all required arguments are present
        // and in the right format, if not then set an appropriate error
        // message and return
        if (empty($args['basedir'])) {
            throw new EmptyParameterException('basedir');
        }
        if (empty($args['filetype'])) {
            throw new EmptyParameterException('filetype');
        }

        // Security check - we require OVERVIEW rights here for now...
        if (!$this->sec()->checkAccess('ViewDynamicData')) {
            return;
        }

        // Get arguments from argument array
        extract($args);
        /** @var string $basedir */
        /** @var string $filetype */

        if (empty($filematch)) {
            $filematch = '';
        }
        if (!isset($recursive)) {
            $recursive = true;
        }

        $todo = [];
        $basedir = realpath($basedir);
        $filelist = [];
        array_push($todo, $basedir);
        while (count($todo) > 0) {
            $curdir = array_shift($todo);
            if ($dir = @opendir($curdir)) {
                while (($file = @readdir($dir)) !== false) {
                    $curfile = $curdir . '/' . $file;
                    if (preg_match("/$filematch\.$filetype$/", $file) && is_file($curfile)) {
                        // ugly fix for Windows boxes
                        $tmpdir = strtr($basedir, ['\\' => '\\\\']);
                        $curfile = preg_replace("#$tmpdir/#", '', $curfile);
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
}
