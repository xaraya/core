<?php
/**
 * Utility function to replace %%calls%%
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */
/**
 * utility function utility function to replace %%calls%%
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 * @return array containing the search and replace items
 */
function mail_adminapi_replace(Array $args=array())
{
    extract($args);

    $sitename   = xarModVars::get('themes', 'SiteName');
    $siteslogan = xarModVars::get('themes', 'SiteSlogan');
    $siteadmin  = xarModVars::get('mail', 'adminname');
    $siteurl    = xarServer::getBaseURL();

    $name = xarUserGetVar('name');
    $id = xarUserGetVar('id');

    $search = array('/%%name%%/',
                    '/%%sitename%%/',
                    '/%%siteslogan%%/',
                    '/%%siteurl%%/',
                    '/%%id%%/',
                    '/%%siteadmin%%/');

    $replace = array("$name",
                     "$sitename",
                     "$siteslogan",
                     "$siteurl",
                     "$id",
                     "$siteadmin");

    $searchstrings = xarModVars::get('mail','searchstrings');
    if (!empty($searchstrings)) {
        $searchstrings = unserialize($searchstrings);
        $searchstrings = explode("\r\n", $searchstrings);
        foreach ($searchstrings as $key) {
            $search[] = '/'. $key .'/';
        }
    }

    $replacestrings = xarModVars::get('mail','replacestrings');
    if (!empty($replacestrings)) {
        $replacestrings = unserialize($replacestrings);
        $replacestrings = explode("\r\n", $replacestrings);
        foreach ($replacestrings as $key) {
            $replace[] = $key;
        }
    }

    $message = preg_replace($search,
                            $replace,
                            $message);

    $subject = preg_replace($search,
                            $replace,
                            $subject);

    $htmlmessage = preg_replace($search,
                                $replace,
                                $htmlmessage);


    return array('message'      => $message,
                 'subject'      => $subject,
                 'htmlmessage'  => $htmlmessage);

}
?>
