<?php
/**
 * File: $Id$
 *
 * register all css related tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author AndyV_at_Xaraya_dot_Com
 * @todo none
 */

/**
 * register all css template tags
 *
 * @author Andy Varganov
 * @param none
 * @returns bool
 */
function themes_cssapi_registercsstags($args)
{

    // just resetting default tags here, nothing else

    // unregister all - just in case they got corrupted or fiddled with via gui
    xarTplUnregisterTag('additional-styles');
    xarTplUnregisterTag('style');

    xarTplUnregisterTag('link-module-stylesheet');
    xarTplUnregisterTag('import-module-stylesheet');
    xarTplUnregisterTag('embed-module-styles');

    xarTplUnregisterTag('link-theme-stylesheet');
    xarTplUnregisterTag('import-theme-stylesheet');
    xarTplUnregisterTag('embed-theme-styles');

    xarTplUnregisterTag('link-common-stylesheet');
    xarTplUnregisterTag('import-common-stylesheet');

    // use in module templates to add styles by using different linking and embedding methods

    // LINK FROM MODULE
    $cssTagAttributes = array(  new xarTemplateAttribute('modname',     XAR_TPL_REQUIRED | XAR_TPL_STRING),
                                new xarTemplateAttribute('filename',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('themedir',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('alternate',   XAR_TPL_OPTIONAL | XAR_TPL_BOOLEAN),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('title',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'link-module-stylesheet', $cssTagAttributes ,'themes_cssapi_linkmodule');

    // IMPORT FROM MODULE
    $cssTagAttributes = array(  new xarTemplateAttribute('modname',     XAR_TPL_REQUIRED | XAR_TPL_STRING),
                                new xarTemplateAttribute('filename',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('themedir',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'import-module-stylesheet', $cssTagAttributes ,'themes_cssapi_importmodule');

    // EMBED FROM MODULE
    $cssTagAttributes = array(  new xarTemplateAttribute('source',      XAR_TPL_REQUIRED | XAR_TPL_STRING),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'embed-module-styles', $cssTagAttributes , 'themes_cssapi_embedmodule');

    // use in theme templates to add styles by using different linking and embedding methods

    // LINK FROM THEME
    $cssTagAttributes = array(  new xarTemplateAttribute('filename',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('alternate',   XAR_TPL_OPTIONAL | XAR_TPL_BOOLEAN),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('title',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'link-theme-stylesheet', $cssTagAttributes ,'themes_cssapi_linktheme');

    // IMPORT FROM THEME
    $cssTagAttributes = array(  new xarTemplateAttribute('filename',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'import-theme-stylesheet', $cssTagAttributes , 'themes_cssapi_importtheme');

    // EMBED FROM THEME
    $cssTagAttributes = array(  new xarTemplateAttribute('source',      XAR_TPL_REQUIRED | XAR_TPL_STRING),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'embed-theme-styles', $cssTagAttributes , 'themes_cssapi_embedtheme');


    // LINK COMMON FROM ANY TEMPLATE
    $cssTagAttributes = array(  new xarTemplateAttribute('filename',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'link-common-stylesheet', $cssTagAttributes ,'themes_cssapi_linkcommon');

    // IMPORT COMMON FROM ANY TEMPLATE
    $cssTagAttributes = array(  new xarTemplateAttribute('filename',    XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('media',       XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'import-common-stylesheet', $cssTagAttributes , 'themes_cssapi_importcommon');

// NEW TAGS
// use in theme to render all extra styles tags
   xarTplRegisterTag( 'themes', 'additional-styles', array(), 'themes_cssapi_delivercss');

    // Register the tag which is used to include style information
    $cssTagAttributes = array(  new xarTemplateAttribute('filename' , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('scope'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('method'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('modname'  , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('themename', XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('type'     , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('alternate', XAR_TPL_OPTIONAL | XAR_TPL_BOOLEAN),
                                new xarTemplateAttribute('media'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('title'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING));
    xarTplRegisterTag( 'themes', 'style', $cssTagAttributes ,'themes_cssapi_registercss');
   // return
    return true;
}

?>