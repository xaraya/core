<?php
/**
 * Get output buffer(s) (e.g. before trying to send back some file or image)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage base
 */

/**
 * Get output buffer(s) (e.g. before trying to send back some file or image)
 *
 * @author Carl P. Corliss
 * @author the Base module development team
 * @return array containing the contents of the different output buffers
 */
function base_userapi_get_output_buffer()
{
    $pageBuffer = array();
    if (ini_get('output_handler') == 'ob_gzhandler' || ini_get('zlib.output_compression') == TRUE) {
        do {
            $contents = ob_get_contents();
            if (!strlen($contents)) {
                // Assume we have nothing to store
                $pageBuffer[] = '';
                break;
            } else {
                $pageBuffer[] = $contents;
            }
        } while (@ob_end_clean());
    } else {
        do {
            $pageBuffer[] = ob_get_contents();
        } while (@ob_end_clean());
    }

    $buffer = array_reverse($pageBuffer);

    return $buffer;
}
?>
