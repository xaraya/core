<?php 
/**
 * File: $Id$
 *
 * Displays a HTML editible Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage Base Module
 * @author Patrick Kellum
*/

/**
 * Block init - holds security.
 */
function themes_metablock_init()
{
    // Security
    xarSecAddSchema('themes:metablock', 'Block title::');
}

/**
 * block information array
 */
function themes_metablock_info()
{
    return array('text_type' => 'Meta',
		 'text_type_long' => 'Meta',
		 'module' => 'themes',
         'func_update' => 'themes_metablock_update',
		 'allow_multiple' => false,
		 'form_content' => false,
		 'form_refresh' => false,
		 'show_preview' => true);

}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function themes_metablock_display($blockinfo)
{
    if (!xarSecAuthAction(0, 'themes:metablock', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }

    // Get current content
    $vars = @unserialize($blockinfo['content']);

    $incomingdesc = xarVarGetCached('Blocks.articles','summary');

    if (!empty($incomingdesc)){
        // Strip -all- html
        $htmlless = strip_tags($incomingdesc);
        $meta['description'] = $htmlless;
    } else {
        $meta['description'] = $vars['metadescription'];
    }

    $incomingkey = xarVarGetCached('Blocks.articles','body');

    if (!empty($incomingkey)){
       
        // Strip -all- html
        $htmlless = strip_tags($incomingkey);
        
        // Strip anything that isn't alphanumeric or _ - 
        $symbolLess = trim(ereg_replace('([^a-zA-Z0-9_-])+',' ',$htmlless));
        
        // Remove duplicate words
        $keywords = explode(" ", strtolower($symbolLess));
        $keywords = array_unique($keywords);
        
        // Remove words that are < four characters in length
        foreach($keywords as $word) {
            if (strlen($word) >= 4 && !empty($word)) {
                $list[] = $word;
            }
        } $keywords = $list;
        
        // Sort the list of words in Ascending order Alphabetically
        sort($keywords, SORT_STRING);
        
        // Merge the list of words into a single, comma delimited string of keywords
        $meta['keywords'] = implode(",",$keywords);
    
    } else {
        $meta['keywords'] = $vars['metakeywords'];
    }

    $meta['generator'] = xarConfigGetVar('System.Core.VersionNum');
    $meta['generator'] .= ' :: ';
    $meta['generator'] .= xarConfigGetVar('System.Core.VersionID');

    if (!empty($args['geourl'])){
        $meta['longitude'] = $args['longitude'];
        $meta['latitude'] = $args['latitude'];
    }

    $meta['activepage'] = xarServerGetCurrentURL();
    
    $blockinfo['content'] = $meta;
    return $blockinfo;
 
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function themes_metablock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['metakeywords'])) {
        $vars['metakeywords'] = '';
    }
    // Defaults
    if (empty($vars['metadescription'])) {
        $vars['metadescription'] = '';
    }
    // Defaults
    if (empty($vars['usegeo'])) {
        $vars['usegeo'] = '';
    }
    // Defaults
    if (empty($vars['longitude'])) {
        $vars['longitude'] = '';
    }
    // Defaults
    if (empty($vars['latitude'])) {
        $vars['latitude'] = '';
    }
    
    $content = xarTplBlock('themes', 'metaAdmin', $vars);

    return $content;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function themes_metablock_update($blockinfo)
{
    list($vars['metakeywords'],
         $vars['metadescription'],
         $vars['usegeo'],
         $vars['longitude'],
         $vars['latitude']) = xarVarCleanFromInput('metakeywords',
                                                   'metadescription',
                                                   'usegeo',
                                                   'longitude',
                                                   'latitude');

    // Defaults
    if (empty($vars['metakeywords'])) {
        $vars['metakeywords'] = '';
    }
    
    if (empty($vars['metadescription'])) {
        $vars['metadescription'] = '';
    }

    if (empty($vars['usegeo'])) {
        $vars['usegeo'] = '';
    }

    if (empty($vars['longitude'])) {
        $vars['longitude'] = '';
    }

    if (empty($vars['latitude'])) {
        $vars['latitude'] = '';
    }

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>