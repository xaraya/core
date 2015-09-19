<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Function to read a file
 * 
 * @param array $args Function parameters
 * @param string $args['file'] File to be opened.
 * @return boolean|string Return either the file contents or false if no file was given.
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