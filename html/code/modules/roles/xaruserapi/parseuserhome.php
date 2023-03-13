<?php
/**
 * Determine User Home URL
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * @desct
 * @param array    $args array of optional parameters<br/>
 *        string   $args['url'] to check<br/>
 *        string   $args['truecurrenturl'] calling url<br/>
 *        string   $args['redirecturl'] page to return user
 * @return array|void true if external URL
 */
function roles_userapi_parseuserhome(Array $args=array())
{
    extract($args);
    if(!isset($url) || !isset($truecurrenturl)) {
        throw new BadParameterException(null,'Wrong arguments to roles_userapi_parseuserhome.');
    }

    $data=array();
    $externalurl=false; //used as a flag for userhome external url
    // FIXME: this probably causes bug #3393
    $here = (substr($truecurrenturl, -strlen($url)) == $url) ? 'true' : '';
    if (!empty($url)){
        switch ($url[0])
        {
            case '[': // module link
            {
                // Credit to Elek M?ton for further expansion
                $sections = explode(']',substr($url,1));
                $url = explode(':', $sections[0]);
                // if the current module is active, then we are here
/*                        if ($url[0] == $thismodname &&
                            (!isset($url[1]) || $url[1] == $thismodtype) &&
                            (!isset($url[2]) || $url[2] == $thisfuncname)) {
                            $here = 'true';
                        }
*/
                if (empty($url[1])) $url[1]="user";
                    if (empty($url[2])) $url[2]="main";
                    $url = xarController::URL($url[0],$url[1],$url[2]);
                if(isset($sections[1])) {
                   $url .= xarVar::prepForDisplay($sections[1]);
                }
                break;
            }
            case '{': // article link
            {
                $url = explode(':', substr($url, 1,  - 1));
                 // Get current pubtype type (if any)
                if (xarVar::isCached('Blocks.articles', 'ptid')) {
                    $ptid = xarVar::getCached('Blocks.articles', 'ptid');
                }
                if (empty($ptid)) {
                     // try to get ptid from input
                     xarVar::fetch('ptid', 'isset', $ptid, NULL, xarVar::DONT_SET);
                }
                // if the current pubtype is active, then we are here
                if ($url[0] == $ptid) {
                     $here = 'true';
                }
                $url = xarController::URL('articles', 'user', 'view', array('ptid' => $url[0]));
                break;
            }
            case '(': // category link
            {
                $url = explode(':', substr($url, 1,  - 1));
                if (xarVar::isCached('Blocks.categories','catid')) {
                    $catid = xarVar::getCached('Blocks.categories','catid');
                }
                if (empty($catid)) {
                    // try to get catid from input
                     xarVar::fetch('catid', 'isset', $catid, NULL, xarVar::DONT_SET);
                }
                if (empty($catid) && xarVar::isCached('Blocks.categories','cids')) {
                     $cids = xarVar::getCached('Blocks.categories','cids');
                } else {
                    $cids = array();
                }
                $catid = str_replace('_', '', $catid);
                $ancestors = xarMod::apiFunc('categories','user','getancestors',
                                         array('cid' => $catid,
                                               'cids' => $cids,
                                               'return_itself' => true));
                if(!empty($ancestors)) {
                    $ancestorcids = array_keys($ancestors);
                    if (in_array($url[0], $ancestorcids)) {
                        // if we are on or below this category, then we are here
                        $here = 'true';
                    }
                }
                $url = xarController::URL('articles', 'user', 'view', array('catid' => $url[0]));
                        break;
            }
            default : // standard URL
                $allowexternalurl = (bool)xarModVars::get('roles','allowexternalurl');
                $url_parts = parse_url($url);
                if (isset($url_parts['host'])) { //if not we don't have to worry
                    if (($url_parts['host'] != $_SERVER["SERVER_NAME"]) &&
                        ($url_parts['host'] != $_SERVER["HTTP_HOST"])) {
                        $externalurl=true;
                    }
                    if (!$allowexternalurl && $externalurl) {
                        $msg = 'External URLs such as #(1) are not permitted in your User Account. Please edit your User Home setting or contact Administration to correct this.';
                        throw new BadParameterException($url,$msg);
                    }
                }
                // BUG 2023: Make sure manual URLs are prepped for XML, consistent with xarController::URL()
                if (!empty(xarMod::$genXmlUrls)) {
                    $url = xarVar::prepForDisplay($url);
                }
           }
         }
    $redirecturl = $url;

    $data['redirecturl']=$url;
    $data['externalurl']=$externalurl;

    return $data;
}
