<?php
/**
 * File: $Id: s.xaradmin.php 1.28 03/02/08 17:38:40-05:00 John.Cox@mcnabb. $
 *
 * Mail System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage mail module
 * @author John Cox <admin@dinerminor.com>
 */

/**
 * utility function utility function to replace %%calls%%
 *
 * @author John Cox
 * @returns array
 * @return array containing the search and replace items
 */
function mail_adminapi_replace($args)
{
    extract ($args);

    $sitename   = xarModGetVar('themes', 'SiteName');
    $siteslogan = xarModGetVar('themes', 'SiteSlogan');
    $siteadmin  = xarModGetVar('mail', 'adminname');
    $siteurl    = xarServerGetBaseURL();

    $name = xarUserGetVar('name');
    $uid = xarUserGetVar('uid');

    $search = array('/%%name%%/',
                    '/%%sitename%%/',
                    '/%%siteslogan%%/',
                    '/%%siteurl%%/',
                    '/%%uid%%/',
                    '/%%siteadmin%%/');

    $replace = array("$name",
                     "$sitename",
                     "$siteslogan",
                     "$siteurl",
                     "$uid",
                     "$siteadmin");

    $searchstrings = xarModGetVar('mail','searchstrings');
    if (!empty($searchstrings)) {
        $searchstrings = unserialize($searchstrings);
        $searchstrings = explode("\r\n", $searchstrings);
        foreach ($searchstrings as $key) {
            $search[] = '/'. $key .'/';
        }
    }

    $replacestrings = xarModGetVar('mail','replacestrings');
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