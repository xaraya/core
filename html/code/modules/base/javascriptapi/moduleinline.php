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

sys::import('modules.base.method');

/**
 * base javascriptapi moduleinline function
 * @extends MethodClass<JavascriptApi>
 */
class ModuleinlineMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Base JavaScript management functions
     * Include a section of inline JavaScript code in a page.
     * Used when a module needs to generate custom JS on-the-fly,
     * such as "var lang_msg = $this->ml('error - aborted');"
     * @author Jason Judge
     * @param mixed $args ['position'] position on the page; generally 'head' or 'body'
     * @param mixed $args ['code'] the JavaScript code fragment
     * @param mixed $args ['index'] optional index in the JS array; unique identifier
     * @return bool|void Returns true on success, false on failure
     * @see JavascriptApi::moduleinline()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($code)) {
            return;
        }

        // Use a hash index to prevent the same JS code fragment
        // from being included more than once.
        if (empty($index)) {
            $index = md5($code);
        }

        // Default the position to the head.
        if (empty($position)) {
            $position = 'head';
        }

        // @fixme replace with right javascript code or drop function
        //return xarTplAddJavaScript($position, 'code', $code, $index);
        return $this->mod()->apiFunc('themes', 'user', 'registerjs', ['position' => $position, 'code' => $code, 'index' => $index]);
    }
}
