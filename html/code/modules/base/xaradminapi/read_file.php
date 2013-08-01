<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */

function base_adminapi_read_file($args)
{
    if (empty($args['file'])) return false;
    try {
        $data = "";
        if (file_exists($args['file'])) {
            $fp = fopen($args['file'], "rb");
            while (!feof($fp)) {
                $filestring = fread($fp, 4096);
                $data .=  $filestring;
            }
            fclose ($fp);
        }
        return $data ;
    } catch (Exception $e) {
        return '';
    }
}

?>