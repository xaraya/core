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
    if(!xarSecurityCheck('ViewThemes',0,'metablock',"$blockinfo[title]:All:All",'All')) return;

    // Get current content
    $vars = @unserialize($blockinfo['content']);

    $incomingdesc = xarVarGetCached('Blocks.articles','summary');

    if ((!empty($incomingdesc)) and ($vars['usedk'] == 1)){
        // Strip -all- html
        $htmlless = strip_tags($incomingdesc);
        $meta['description'] = $htmlless;
    } else {
        $meta['description'] = $vars['metadescription'];
    }

    $incomingkey = xarVarGetCached('Blocks.articles','body');

    if ((!empty($incomingkey)) and ($vars['usedk'] == 1)){

        // Keywords generated from articles module
        $meta['keywords'] = xarVarGetCached('Blocks.articles','body');

    } else {
        $meta['keywords'] = $vars['metakeywords'];
    }

    $meta['charset'] = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());

    $meta['generator'] = xarConfigGetVar('System.Core.VersionId');
    $meta['generator'] .= ' :: ';
    $meta['generator'] .= xarConfigGetVar('System.Core.VersionNum');

    if (!empty($vars['usegeo'])){
        $meta['longitude'] = $vars['longitude'];
        $meta['latitude'] = $vars['latitude'];
    }

    $meta['geourl'] = $vars['usegeo'];

    $meta['activepage'] = preg_replace('/&[^amp;]/', '&amp;', xarServerGetCurrentURL());

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
    if(!xarVarFetch('metakeywords',    'str:1', $vars['metakeywords'],    '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('metadescription', 'str:1', $vars['metadescription'], '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('usegeo',          'str:1', $vars['usegeo'],          '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('usedk',           'str:1', $vars['usedk'],           '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('longitude',       'str:1', $vars['longitude'],       '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('latitude',        'str:1', $vars['latitude'],        '', XARVAR_NOT_REQUIRED)) {return;}

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>
