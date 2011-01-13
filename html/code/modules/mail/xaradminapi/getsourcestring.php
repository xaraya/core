<?php
/**
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * Psspl : Added API function to read the contents of template files (.xt) as plain text
 * @param array    $args array of optional parameters<br/>
 */
function mail_adminapi_getsourcestring(Array $args=array())
{   
    $sourceFileName = xarMod::apiFunc('mail', 'admin', 'getsourcefilename', $args);      
    if (!file_exists($sourceFileName)) throw new FileNotFoundException($sourceFileName);
    $string = '';
    $fd = fopen($sourceFileName, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $message = $string;
    fclose($fd);
    $message = str_replace('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">',
                            '',
                            $message);
    $message = str_replace('</xar:template>',
                            '',
                            $message);  
    return $message;
}
?>