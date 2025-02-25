<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use xarModVars;
use xarServer;
use xarUser;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi replace function
 * @extends MethodClass<AdminApi>
 */
class ReplaceMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function utility function to replace %%calls%%
     * @author John Cox <niceguyeddie@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array containing the search and replace items
     * @see AdminApi::replace()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $sitename   = $this->mod('themes')->getVar('SiteName');
        $siteslogan = $this->mod('themes')->getVar('SiteSlogan');
        $siteadmin  = $this->mod()->getVar('adminname');
        $siteurl    = $this->ctl()->getBaseURL();

        $name = xarUser::getVar('name');
        $id = xarUser::getVar('id');

        $search = ['/%%name%%/',
            '/%%sitename%%/',
            '/%%siteslogan%%/',
            '/%%siteurl%%/',
            '/%%id%%/',
            '/%%siteadmin%%/'];

        $replace = ["$name",
            "$sitename",
            "$siteslogan",
            "$siteurl",
            "$id",
            "$siteadmin"];

        $searchstrings = $this->mod()->getVar('searchstrings');
        if (!empty($searchstrings)) {
            $searchstrings = unserialize($searchstrings);
            $searchstrings = explode("\r\n", $searchstrings);
            foreach ($searchstrings as $key) {
                $search[] = '/' . $key . '/';
            }
        }

        $replacestrings = $this->mod()->getVar('replacestrings');
        if (!empty($replacestrings)) {
            $replacestrings = unserialize($replacestrings);
            $replacestrings = explode("\r\n", $replacestrings);
            foreach ($replacestrings as $key) {
                $replace[] = $key;
            }
        }

        $message = preg_replace(
            $search,
            $replace,
            $message
        );

        $subject = preg_replace(
            $search,
            $replace,
            $subject
        );

        $htmlmessage = preg_replace(
            $search,
            $replace,
            $htmlmessage
        );


        return ['message'      => $message,
            'subject'      => $subject,
            'htmlmessage'  => $htmlmessage];

    }
}
