<?php
/**
 * View recent module releases via central repository
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * View recent module releases via central repository
 *
 * @author Xaraya Development Team
 * @access public
 * @param none
 * @returns array
 * @todo change feed url once release module is moved
 */
function modules_admin_release()
{
    // Security Check
    if(!xarSecurityCheck('EditModules')) return;
    // allow fopen
    if (!xarFuncIsDisabled('ini_set')) ini_set('allow_url_fopen', 1);
    if (!ini_get('allow_url_fopen')) {
        throw new ConfigurationException('allow_url_fopen','PHP is not currently configured to allow URL retrieval
                             of remote files.  Please turn on #(1) to use the base module getfile userapi.');
    }
    // Require the xmlParser class
    require_once('modules/base/xarclass/xmlParser.php');
    // Require the feedParser class
    require_once('modules/base/xarclass/feedParser.php');
    // Check and see if a feed has been supplied to us.
    // Need to change the url once release module is moved to 
    $feedfile = "http://www.xaraya.com/index.php/articles/rnid/?theme=rss";
    // Get the feed file (from cache or from the remote site)
    $feeddata = xarModAPIFunc('base', 'user', 'getfile',
                              array('url' => $feedfile,
                                    'cached' => true,
                                    'cachedir' => 'cache/rss',
                                    'refresh' => 604800,
                                    'extension' => '.xml'));
    if (!$feeddata) return;
    // Create a need feedParser object
    $p = new feedParser();
    // Tell feedParser to parse the data
    $info = $p->parseFeed($feeddata);
    if (empty($info['warning'])){
        foreach ($info as $content){
             foreach ($content as $newline){
                    if(is_array($newline)) {
                        if (isset($newline['description'])){
                            $description = $newline['description'];
                        } else {
                            $description = '';
                        }
                        if (isset($newline['title'])){
                            $title = $newline['title'];
                        } else {
                            $title = '';
                        }
                        if (isset($newline['link'])){
                            $link = $newline['link'];
                        } else {
                            $link = '';
                        }
                    $feedcontent[] = array('title' => $title, 'link' => $link, 'description' => $description);
                }
            }
        }
        $data['chantitle']  =   $info['channel']['title'];
        $data['chanlink']   =   $info['channel']['link'];
        $data['chandesc']   =   $info['channel']['description'];
    } else {
        $msg = xarML('There is a problem with a feed.');
        throw new Exception($msg);
    }
    $data['feedcontent'] = $feedcontent;
    return $data;
}
?>
