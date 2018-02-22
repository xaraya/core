<?php
/**
 * Get output buffer
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Get output buffer(s) (e.g. before trying to send back some file or image)
 *
 * @author Carl P. Corliss
 * @author the Base module development team
 * 
 * @param void N/A
 * @return Array containing the contents of the different output buffers
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