<?php
/**
 * File: $Id$
 *
 * Displays a Editible Meta Values
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Themes Module
 * @author Carl Corliss, John Cox
*/

/**
 * initialise block
 *
 * @author  John Cox
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_init()
{
    return true;
}

/**
 * get information on block
 *
 * @author  John Cox
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
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
 * display adminmenu block
 *
 * @author  Carl Corliss, John Cox
 * @access  public
 * @param   $blockinfo array containing usegeo, metakeywords, metadescription, longitude, latitude, usedk.
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    complete
*/
function themes_metablock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"meta:$blockinfo[title]:All")) return;

    // Get current content
    $vars = @unserialize($blockinfo['content']);
    // Description
    $incomingdesc = xarVarGetCached('Blocks.articles','summary');
    if ((!empty($incomingdesc)) and ($vars['usedk'] == 1)){
        // Strip -all- html
        $htmlless = strip_tags($incomingdesc);
        $meta['description'] = $htmlless;
    } else {
        $meta['description'] = $vars['metadescription'];
    }
    // Dynamic Keywords
    $incomingkey = xarVarGetCached('Blocks.articles','body');
    if ((!empty($incomingkey)) and ($vars['usedk'] == 1)){
        // Keywords generated from articles module
        $meta['keywords'] = xarVarGetCached('Blocks.articles','body');
    } else {
        $meta['keywords'] = $vars['metakeywords'];
    }
    // Character Set
    $meta['charset'] = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $meta['generator'] = xarConfigGetVar('System.Core.VersionId');
    $meta['generator'] .= ' :: ';
    $meta['generator'] .= xarConfigGetVar('System.Core.VersionNum');
    // Geo Url
    $meta['longitude'] = $vars['longitude'];
    $meta['latitude'] = $vars['latitude'];
    // Active Page
    $meta['activepage'] = preg_replace('/&[^amp;]/', '&amp;', xarServerGetCurrentURL());

    $meta['baseurl'] = xarServerGetBaseUrl();
    if (isset($vars['copyrightpage'])){
        $meta['copyrightpage'] = $vars['copyrightpage'];
    } else {
        $meta['copyrightpage'] = '';
    }
    if (isset($vars['helppage'])){
            $meta['helppage'] = $vars['helppage'];
    } else {
        $meta['helppage'] = '';
    }
    if (isset($vars['glossary'])){
            $meta['glossary'] = $vars['glossary'];
    } else {
        $meta['glossary'] = '';
    }
    //Pager Buttons
    $meta['first'] = xarVarGetCached('Pager.first','leftarrow');
    $meta['last']  = xarVarGetCached('Pager.last','rightarrow');

    $blockinfo['content'] = $meta;
    return $blockinfo;

}

/**
 * modify block settings
 *
 * @author  John Cox
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
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
    if (empty($vars['usedk'])) {
        $vars['usedk'] = '';
    }
    // Defaults
    if (empty($vars['longitude'])) {
        $vars['longitude'] = '';
    }
    // Defaults
    if (empty($vars['latitude'])) {
        $vars['latitude'] = '';
    }
    // Defaults
    if (empty($vars['copyrightpage'])) {
        $vars['copyrightpage'] = '';
    }
    // Defaults
    if (empty($vars['helppage'])) {
        $vars['helppage'] = '';
    }
    // Defaults
    if (empty($vars['glossary'])) {
        $vars['glossary'] = '';
    }

    $vars['blockid'] = $blockinfo['bid'];
    $content = xarTplBlock('themes', 'metaAdmin', $vars);

    return $content;
}

/**
 * update block settings
 *
 * @author  John Cox
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_update($blockinfo)
{
    if(!xarVarFetch('metakeywords',    'notempty', $vars['metakeywords'],    '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('metadescription', 'notempty', $vars['metadescription'], '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('usegeo',          'notempty', $vars['usegeo'],          '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('usedk',           'notempty', $vars['usedk'],           '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('longitude',       'notempty', $vars['longitude'],       '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('latitude',        'notempty', $vars['latitude'],        '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('copyrightpage',   'notempty', $vars['copyrightpage'],   '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('helppage',        'notempty', $vars['helppage'],        '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('glossary',        'notempty', $vars['glossary'],        '', XARVAR_NOT_REQUIRED)) return;
    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>
