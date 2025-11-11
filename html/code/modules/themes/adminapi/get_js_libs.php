<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use xarJS;

/**
 * themes adminapi get_js_libs function
 * @extends MethodClass<AdminApi>
 */
class GetJsLibsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\themes
     * @subpackage themes
     * @copyright see the html/credits.html file in this release
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/70.html
     * @see AdminApi::getJsLibs()
     */
    public function __invoke(array $args = [])
    {
        $instance = xarJS::getInstance();
        if (empty($args['scope']) || ($args['scope'] == 'local')) {
            $args['scope'] = 'local_libs';
        } else {
            $args['scope'] = 'remote_libs';
        }

        // Retrieve the libraries of the chosen scope: local or remote
        $libs = $instance->{$args['scope']};

        // If we have a specific lib we are looking for, then filter
        if (!empty($args['lib'])) {
            $result = [];
            foreach ($libs as $lib) {
                if ($lib['lib'] == $args['lib']) {
                    $result[] = $lib;
                }
            }
        } else {
            $result = $libs;
        }

        return $result;
    }
}
