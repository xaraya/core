<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use BadParameterException;

/**
 * roles userapi parseuserhome function
 * @extends MethodClass<UserApi>
 */
class ParseuserhomeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @desct
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['url'] to check<br/>
     * string   $args['truecurrenturl'] calling url<br/>
     * string   $args['redirecturl'] page to return user
     * @return array|void true if external URL
     * @see UserApi::parseuserhome()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($url) || !isset($truecurrenturl)) {
            throw new BadParameterException(null, 'Wrong arguments to roles_userapi_parseuserhome.');
        }

        $data = [];
        $externalurl = false; //used as a flag for userhome external url
        // FIXME: this probably causes bug #3393
        $here = (substr($truecurrenturl, -strlen($url)) == $url) ? 'true' : '';
        if (!empty($url)) {
            switch ($url[0]) {
                case '[': // module link
                    {
                        // Credit to Elek M?ton for further expansion
                        $sections = explode(']', substr($url, 1));
                        $url = explode(':', $sections[0]);
                        // if the current module is active, then we are here
                        /*                        if ($url[0] == $thismodname &&
                                                    (!isset($url[1]) || $url[1] == $thismodtype) &&
                                                    (!isset($url[2]) || $url[2] == $thisfuncname)) {
                                                    $here = 'true';
                                                }
                        */
                        if (empty($url[1])) {
                            $url[1] = "user";
                        }
                        if (empty($url[2])) {
                            $url[2] = "main";
                        }
                        $url = $this->ctl()->getModuleURL($url[0], $url[1], $url[2]);
                        if (isset($sections[1])) {
                            $url .= $this->prep()->text($sections[1]);
                        }
                        break;
                    }
                case '{': // article link
                    {
                        $url = explode(':', substr($url, 1, - 1));
                        // Get current pubtype type (if any)
                        if ($this->mem()->has('Blocks.articles', 'ptid')) {
                            $ptid = $this->mem()->get('Blocks.articles', 'ptid');
                        }
                        if (empty($ptid)) {
                            // try to get ptid from input
                            $this->var()->check('ptid', $ptid);
                        }
                        // if the current pubtype is active, then we are here
                        if ($url[0] == $ptid) {
                            $here = 'true';
                        }
                        $url = $this->ctl()->getModuleURL('articles', 'user', 'view', ['ptid' => $url[0]]);
                        break;
                    }
                case '(': // category link
                    {
                        $url = explode(':', substr($url, 1, - 1));
                        if ($this->mem()->has('Blocks.categories', 'catid')) {
                            $catid = $this->mem()->get('Blocks.categories', 'catid');
                        }
                        if (empty($catid)) {
                            // try to get catid from input
                            $this->var()->check('catid', $catid);
                        }
                        if (empty($catid) && $this->mem()->has('Blocks.categories', 'cids')) {
                            $cids = $this->mem()->get('Blocks.categories', 'cids');
                        } else {
                            $cids = [];
                        }
                        $catid = str_replace('_', '', $catid);
                        $ancestors = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getancestors',
                            ['cid' => $catid,
                                'cids' => $cids,
                                'return_itself' => true]
                        );
                        if (!empty($ancestors)) {
                            $ancestorcids = array_keys($ancestors);
                            if (in_array($url[0], $ancestorcids)) {
                                // if we are on or below this category, then we are here
                                $here = 'true';
                            }
                        }
                        $url = $this->ctl()->getModuleURL('articles', 'user', 'view', ['catid' => $url[0]]);
                        break;
                    }
                default: // standard URL
                    $allowexternalurl = (bool) $this->mod()->getVar('allowexternalurl');
                    $url_parts = parse_url($url);
                    if (isset($url_parts['host'])) { //if not we don't have to worry
                        if (($url_parts['host'] != $this->req()->getServerVar("SERVER_NAME"))
                            && ($url_parts['host'] != $this->req()->getServerVar("HTTP_HOST"))
                            && ($url_parts['host'] != 'localhost')) {
                            $externalurl = true;
                        }
                        if (!$allowexternalurl && $externalurl) {
                            $msg = 'External URLs such as #(1) are not permitted in your User Account. Please edit your User Home setting or contact Administration to correct this.';
                            throw new BadParameterException($url, $msg);
                        }
                    }
                    // BUG 2023: Make sure manual URLs are prepped for XML, consistent with $this->ctl()->getModuleURL()
                    if ($this->ctl()->withXMLURLs()) {
                        $url = $this->prep()->text($url);
                    }
            }
        }
        $redirecturl = $url;

        $data['redirecturl'] = $url;
        $data['externalurl'] = $externalurl;

        return $data;
    }
}
