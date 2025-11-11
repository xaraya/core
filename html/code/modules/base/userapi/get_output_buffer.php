<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\UserApi;

/**
 * base userapi get_output_buffer function
 * @extends MethodClass<UserApi>
 */
class GetOutputBufferMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get output buffer(s) (e.g. before trying to send back some file or image)
     * @author Carl P. Corliss
     * @author the Base module development team
     * @return array containing the contents of the different output buffers
     * @see UserApi::getOutputBuffer()
     */
    public function __invoke(array $args = [])
    {
        $pageBuffer = [];
        if (ini_get('output_handler') == 'ob_gzhandler' || ini_get('zlib.output_compression') == true) {
            do {
                $contents = ob_get_contents();
                if (!strlen($contents)) {
                    // Assume we have nothing to store
                    $pageBuffer[] = '';
                    break;
                } else {
                    $pageBuffer[] = $contents;
                }
            } while (ob_get_level() && ob_end_clean());
        } else {
            do {
                $pageBuffer[] = ob_get_contents();
            } while (ob_get_level() && ob_end_clean());
        }

        $buffer = array_reverse($pageBuffer);

        return $buffer;
    }
}
