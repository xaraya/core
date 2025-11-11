<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\JavascriptApi;

use Xaraya\Modules\Base\MethodClass;
use Xaraya\Modules\Base\JavascriptApi;
use sys;

/**
 * base javascriptapi _findfile function
 * @extends MethodClass<JavascriptApi>
 */
class FindfileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Base JavaScript management functions
     * Find the path for a JavaScript file.
     * @author Jason Judge
     * @param mixed $args ['module'] Module name; or
     * @param mixed $args ['moduleid'] module ID (deprecated)
     * @param mixed $args ['modid'] module ID
     * @param mixed $args ['filename'] file name
     * @return string|void the virtual pathname for the JS file; an empty value if not found
     * @checkme: The default module should be the current *template* module, not the *request* module?
     * @see JavascriptApi::findfile()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // File must be supplied and may include a path.
        if (empty($filename) || $filename != strval($filename)) {
            return;
        }

        // Bug 5910: If the path has GET parameters, then move them aside for now.
        if (strpos($filename, '?') > 0) {
            [$filename, $params] = explode('?', $filename, 2);
            $params = '?' . $params;
        } else {
            $params = '';
        }

        // Use the current module if none supplied.
        if (empty($module) && empty($modid)) {
            $module = $this->ctl()->getRequest()->getModule();
        }

        // Get the module ID from the module name.
        if (empty($modid) && !empty($module)) {
            $modid = $this->mod()->getRegID($module);
        }

        // Get details for the module if we have a valid module id.
        if (!empty($modid)) {
            $modInfo = $this->mod()->getInfo($modid);

            // Get module directory if we have a valid module.
            if (!empty($modInfo)) {
                $modOsDir = $modInfo['osdirectory'];
            }
        }

        // Theme base directory.
        $themedir = $this->tpl()->getThemeDir();

        // Initialise the search path.
        $searchPath = [];

        // The search path for the JavaScript file.
        $searchPath[] = $themedir . '/scripts/' . $filename;

        // A property attribute in the tag overrides a module attribute
        if (!empty($property)) {
            $searchPath[] = $themedir . '/properties/' . $property . '/scripts/' . $filename;
            $searchPath[] = $themedir . '/properties/' . $property . '/xartemplates/includes/' . $filename;
            $searchPath[] = sys::code() . 'properties/' . $property . '/scripts/' . $filename;
            $searchPath[] = sys::code() . 'properties/' . $property . '/xartemplates/includes/' . $filename;
        } else {
            if (isset($modOsDir)) {
                $searchPath[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
                $searchPath[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename;
                $searchPath[] = sys::code() . 'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;
            }
        }

        foreach ($searchPath as $filePath) {
            if (file_exists($filePath)) {
                break;
            }
            $filePath = '';
        }

        if (empty($filePath)) {
            return;
        }

        return $filePath . $params;
    }
}
