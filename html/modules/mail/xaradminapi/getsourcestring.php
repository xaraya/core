<?php
/**
 * Psspl : Added API function to read the contents of template files (.xd) as plain text
 */
function mail_adminapi_getsourcestring($args)
{   
    $sourceFileName = xarModAPIFunc('mail', 'admin', 'getsourcefilename', $args);      
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