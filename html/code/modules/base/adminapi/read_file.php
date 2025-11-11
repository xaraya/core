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

/**
 * base adminapi read_file function
 * @extends MethodClass<AdminApi>
 */
class ReadFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to read a file
     * @param array<string,mixed> $args Function parameters
     * @param string $args ['file'] File to be opened.
     * @return bool|string Return either the file contents or false if no file was given.
     * @see AdminApi::readFile()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['file'])) {
            return false;
        }
        try {
            $data = "";
            if (file_exists($args['file'])) {
                $fp = fopen($args['file'], "rb");
                while (!feof($fp)) {
                    $filestring = fread($fp, 4096);
                    $data .=  $filestring;
                }
                fclose($fp);
            }
            return $data ;
        } catch (Exception $e) {
            return '';
        }
    }
}
