<?php

/**
 * View recent module releases via central repository
 *
 * @access public
 * @param none
 * @returns array
 * @todo change feed url once release module is moved
 */
function themes_admin_release()
{
    // Security Check
	if(!xarSecurityCheck('EditModules')) return;

    // allow fopen
    ini_set('allow_url_fopen', 1);

    // Require the xmlParser class
    require_once('modules/base/xarclass/xmlParser.php');

    // Require the feedParser class
    require_once('modules/base/xarclass/feedParser.php');


    // Check and see if a feed has been supplied to us.
    // Need to change the url once release module is moved to 
    $feedfile = "http://www.xaraya.com/index.php/articles/rnid/c69/?theme=rss";

    // Create Cache File for 7 days.
    $refresh = (time() - 604800);
    $varDir = xarCoreGetVarDirPath();
    $cacheKey = md5($feedfile);
    $cachedFileName = $varDir . '/cache/rss/' . $cacheKey . '.xml';
    if ((file_exists($cachedFileName)) && (filemtime($cachedFileName) > $refresh)) {
        $fp = @fopen($cachedFileName, 'r');
        // Create a need feedParser object
        $p = new feedParser();
        // Read From Our Cache
        $feeddata = fread($fp, filesize($cachedFileName));
        // Tell feedParser to parse the data
        $info = $p->parseFeed($feeddata);
    } else {
        // Create a need feedParser object
        $p = new feedParser();

        // Read in our sample feed file
        $feeddata = @implode("",@file($feedfile));

        // Tell feedParser to parse the data
        $info = $p->parseFeed($feeddata);
        $fp = fopen("$cachedFileName", "wt");
        fwrite($fp, $feeddata);
        fclose($fp);    
    }

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
        xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }
    
    $data['feedcontent'] = $feedcontent;

    return $data;
}

?>