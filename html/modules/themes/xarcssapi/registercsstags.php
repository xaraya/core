<?php
/**
 * Register all css template tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * register all css template tags
 *
 * @author Andy Varganov AndyV_at_Xaraya_dot_Com
 * @param none
 * @returns bool
 */
function themes_cssapi_registercsstags($args)
{

    // just resetting default tags here, nothing else

    // unregister all - just in case they got corrupted or fiddled with via gui
    xarTplUnregisterTag('additional-styles');
    xarTplUnregisterTag('style');

    // use in theme to render all extra styles tags
   xarTplRegisterTag( 'themes', 'additional-styles', array(), 'themes_cssapi_delivercss');

    // Register the tag which is used to include style information
    $cssTagAttributes = array(  new xarTemplateAttribute('file' , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('scope'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('method'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('module'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('type'     , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('alternate', XAR_TPL_OPTIONAL | XAR_TPL_BOOLEAN),
                                new xarTemplateAttribute('media'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('title'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('source'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('condition', XAR_TPL_OPTIONAL | XAR_TPL_STRING));
    xarTplRegisterTag( 'themes', 'style', $cssTagAttributes ,'themes_cssapi_registercss');
   // return
    return true;
}

?>
