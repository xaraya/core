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
 * Psspl : Added API function to determine the template sourcefile to use
 *
 * @param  string $modName      Module name doing the request *                                 
 * @param  string $modtype      The base name for the template user|admin
 * @param  string $templateName The name for the template to use if any
 * @param  string $mailtype     The type of mail html|text 
 * @param  string $messagepart  The part of the message tobe sent subject|body 
 * @return string
 *
 */
function mail_adminapi_getsourcefilename($args)
{
    extract($args);  
    
    // Template search order:
    // 1. var/messaging/{module}/{type}-{template Name}-{message part}-{mail type}.xt
    // 2. var/messaging/{module}/{type}-{template Name}-{message part}.xt
    // 3. var/messaging/{template Name}-{message part}-{mail type}.xt
    // 4. var/messaging/{template Name}-{message part}.xt
    // 5. complain (later on)
   
    $tplMessagingDir = sys::varpath() . "/messaging/$modName";    
    if (!file_exists($tplMessagingDir)) 
    throw new DirectoryNotFoundException($tplMessagingDir);
    
    unset($sourceFileName);

    xarLogMessage("TPL: 1. $tplMessagingDir/$modType-$templateName-$messagepart-$mailType.xt");
    xarLogMessage("TPL: 2. $tplMessagingDir/$modType-$templateName-$messagepart.xt");
       
    if(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$messagepart-$mailType.xt")) {
        
    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$messagepart.xt")) { 

    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$templateName-$messagepart-$mailType.xt")) { 
            
    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$templateName-$messagepart.xt")) {
            
    } else{
        throw new FileNotFoundException(xarML('No template was found corresponding to #(1) #(2)',$templateName,$messagepart));
    }
    $sourceFileName = str_replace('//','/',$sourceFileName);
    // assert('isset($sourceFileName); /* The source file for the template has no value in xarTplModule */');
    return $sourceFileName;
}

?>