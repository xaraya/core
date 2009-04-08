<?php
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
    // 1. var/messaging/{module}/{type}-{template Name}-{message part}-{mail type}.xd
    // 2. var/messaging/{module}/{type}-{template Name}-{message part}.xd
    // 3. var/messaging/{template Name}-{message part}-{mail type}.xd
    // 4. var/messaging/{template Name}-{message part}.xd
    // 5. complain (later on)
   
    $tplMessagingDir = sys::varpath() . "/messaging/$modName";    
    if (!file_exists($tplMessagingDir)) 
    throw new DirectoryNotFoundException($tplMessagingDir);
    
    unset($sourceFileName);

    xarLogMessage("TPL: 1. $tplMessagingDir/$modType-$templateName-$messagepart-$mailType.xd");
    xarLogMessage("TPL: 2. $tplMessagingDir/$modType-$templateName-$messagepart.xd");
       
    if(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$messagepart-$mailType.xd")) {
        
    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$messagepart.xd")) { 

    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$templateName-$messagepart-$mailType.xd")) { 
            
    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$templateName-$messagepart.xd")) {
            
    } else{
        throw new FileNotFoundException(xarML('No template was found corresponding to #(1) #(2)',$templateName,$messagepart));
    }
    $sourceFileName = str_replace('//','/',$sourceFileName);
    // assert('isset($sourceFileName); /* The source file for the template has no value in xarTplModule */');
    return $sourceFileName;
}

?>