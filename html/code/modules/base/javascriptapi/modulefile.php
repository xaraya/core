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
use xarMod;
use xarServer;
use sys;

sys::import('modules.base.method');

/**
 * base javascriptapi modulefile function
 * @extends MethodClass<JavascriptApi>
 */
class ModulefileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Base JavaScript management functions
     * Include a module JavaScript link in a page.
     * @author Jason Judge
     * @param mixed $args ['module'] module name; or
     * @param mixed $args ['moduleid'] module ID
     * @param mixed $args ['filename'] file name list (comma-separated or array)
     * @param mixed $args ['position'] position on the page; generally 'head' or 'body'
     * @return bool Returns true on success, false on failure
     * @see JavascriptApi::modulefile()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var JavascriptApi $javascriptapi */
        $javascriptapi = $this->javascriptapi();

        $result = true;

        // Default the position to the head.
        if (empty($position)) {
            $position = 'head';
        }

        // Filename can be an array of files to include, or a
        // comma-separated list. This allows a bunch of files
        // to be included from a source module in one go.
        if (!is_array($args['filename'])) {
            $files = explode(',', $args['filename']);
        }

        foreach ($files as $file) {
            $args['filename'] = $file;
            $filePath = $javascriptapi->_findfile($args);

            // A failure to find a file is recorded, but does not stop subsequent files.
            if (!empty($filePath)) {
                //$result = $result & xarTplAddJavaScript($position, 'src', $this->ctl()->getBaseURL() . $filePath, $filePath);
                $result = $result & $this->mod()->apiFunc('themes', 'user', 'registerjs', ['position' => $position, 'src' => $this->ctl()->getBaseURL() . $filePath, 'filename' => $filePath]);

            } else {
                $result = false;
            }
        }

        return $result;
    }
}
