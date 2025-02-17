<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminApi;
use Exception;
use sys;

sys::import('xaraya.modules.method');

/**
 * base adminapi write_file function
 * @extends MethodClass<AdminApi>
 */
class WriteFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to write to a file
     * @param array<string,mixed> $args Function parameters
     * @param string $args ['file'] File name of the file to write to
     * @param string $args ['data'] Data to be written to the file
     * @return bool Returns true on success, false on failure
     * @see AdminApi::writeFile()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['file'])) {
            return false;
        }
        try {
            $fp = fopen($args['file'], "wb");

            /**
            if (get_magic_quotes_gpc()) {
                $data = stripslashes($args['data']);
            }
             */
            fwrite($fp, $args['data']);
            fclose($fp);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
