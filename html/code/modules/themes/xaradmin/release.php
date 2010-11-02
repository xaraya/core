<?php
/**
 * @package modules
 * @subpackage themes module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * View recent module releases via central repository
 *
 * @author Marty Vance
 * @access public
 * @param none
 * @returns array
 * @todo change feed url once release module is moved
 */
function themes_admin_release()
{
    // Security Check
    if(!xarSecurityCheck('EditThemes')) return;
    // allow fopen
    if (!xarFuncIsDisabled('ini_set')) ini_set('allow_url_fopen', 1);
    if (!ini_get('allow_url_fopen')) {
        throw new ConfigurationException('allow_url_fopen','PHP is not currently configured to allow URL retrieval
                             of remote files.  Please turn on #(1) to use the base module getfile userapi.');
    }
    // Require the feedParser class
    sys::import('modules.base.class.feedParser');
    // Check and see if a feed has been supplied to us.
    // Need to change the url once release module is moved to 
    $feedfile = "http://www.xaraya.com/index.php/articles/rnid/c69/?theme=rss";
    // Get the feed file (from cache or from the remote site)
    $feeddata = xarMod::apiFunc('base', 'user', 'getfile',
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