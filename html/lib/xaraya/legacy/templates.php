<?php
/**
 * @package core\templates\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/* REPLACED FUNCTIONS */

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::init()
 * @deprecated
 **/
function xarTpl_init(&$args)
{
    return xarTpl::init($args);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::getThemeName()
 * @deprecated
 **/
function xarTplGetThemeName()
{
    return xarTpl::getThemeName();
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::setThemeName()
 * @deprecated
 **/
function xarTplSetThemeName($themeName)
{
    return xarTpl::setThemeName($themeName);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::setThemeDir()
 * @deprecated
 **/
function xarTplSetThemeDir($themeDir)
{
    return xarTpl::setThemeDir($themeDir);
}
/* Private access, shouldn't be being called outside the xarTpl class
function xarTpl__SetThemeNameAndDir($name)
{
    xarTpl::setThemeNameAndDir($name);
}
*/

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::getThemeDir()
 * @deprecated
 **/
function xarTplGetThemeDir($theme=null)
{
    return xarTpl::getThemeDir($theme);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::getPageTemplateName()
 * @deprecated
 **/
function xarTplGetPageTemplateName()
{
    return xarTpl::getPageTemplateName();
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::setPageTemplateName()
 * @deprecated
 **/
function xarTplSetPageTemplateName($templateName)
{
    return xarTpl::setPageTemplateName($templateName);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::getDoctype()
 * @deprecated
 **/
function xarTplGetDoctype()
{
    return xarTpl::getDoctype();
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::setDoctype()
 * @deprecated
 **/
function xarTplSetDoctype($doctypeName)
{
    return xarTpl::setDoctype($doctypeName);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::setPageTitle()
 * @deprecated
 **/
function xarTplSetPageTitle($title = NULL, $module = NULL)
{
    return xarTpl::setPageTitle($title,$module);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::getPageTitle()
 * @deprecated
 **/
function xarTplGetPageTitle()
{
    return xarTpl::getPageTitle();
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::module()
 * @deprecated
 **/
function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    return xarTpl::module($modName,$modType,$funcName,$tplData,$templateName);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::block()
 * @deprecated
 **/
function xarTplBlock($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL)
{
    return xarTpl::block($modName, $blockType, $tplData, $tplName, $tplBase);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::property()
 * @deprecated
 **/
function xarTplProperty($modName, $propertyName, $tplType = 'showoutput', $tplData = array(), $tplBase = NULL)
{
    return xarTpl::property($modName,$propertyName,$tplType,$tplData,$tplBase);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::object()
 * @deprecated
 **/
function xarTplObject($modName, $objectName, $tplType = 'showdisplay', $tplData = array(), $tplBase = NULL)
{
    return xarTpl::object($modName,$objectName,$tplType,$tplData,$tplBase);
}
/* Private access, shouldn't be being called outside the xarTpl class
function xarTpl__DDElement($modName, $ddName, $tplType, $tplData, $tplBase,$elements)
{
    return xarTpl::DDElement($modName,$ddName,$tplType,$tplData,$tplBase,$elements);
}
*/

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::getImage()
 * @deprecated
 **/
function xarTplGetImage($modImage, $modName = NULL)
{    
    return xarTpl::getImage($modImage,$modName);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::string()
 * @deprecated
 **/
function xarTplString($templateCode, &$tplData)
{
    return xarTpl::string($templateCode,$tplData);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::file()
 * @deprecated
 **/
function xarTplFile($fileName, &$tplData)
{
    return xarTpl::file($fileName,$tplData);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::compileString()
 * @deprecated
 **/
function xarTplCompileString($templateSource)
{
    return xarTpl::compileString($templateSource);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::renderPage()
 * @deprecated
 **/
function xarTpl_renderPage($mainModuleOutput, $pageTemplate = NULL)
{
    return xarTpl::renderPage($mainModuleOutput,$pageTemplate);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::renderBlockBox()
 * @deprecated
 **/
function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    return xarTpl::renderBlockBox($blockInfo,$templateName);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::includeThemeTemplate()
 * @deprecated
 **/
function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    return xarTpl::includeThemeTemplate($templateName,$tplData);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::includeModuleTemplate()
 * @deprecated
 **/
function xarTpl_includeModuleTemplate($modName, $templateName, $tplData, $propertyName='')
{
    return xarTpl::includeModuleTemplate($modName,$templateName,$tplData,$propertyName);
}

// PRIVATE FUNCTIONS

// FIXME: this cannot be private since it's used by the mail module
/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::executeFromFile()
 * @deprecated
 **/
function xarTpl__executeFromFile($sourceFileName, $tplData, $tplType = 'module')
{
    return xarTpl::executeFromFile($sourceFileName, $tplData, $tplType);
}

/* Private access, shouldn't be being called outside the xarTpl class
function xarTpl__getSourceFileName($modName,$tplBase, $templateName = NULL, $tplSubPart = '')
{
    return xarTpl::getSourceFileName($modName,$tplBase,$templateName,$tplSubPart);
}
*/

// END PRIVATE FUNCTIONS

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::outputTemplate()
 * @deprecated
 **/
function xarTpl_outputTemplate($sourceFileName, &$tplOutput)
{
    return xarTpl::outputTemplate($sourceFileName,$tplOutput);
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::outputPHPCommentBlockInTemplates()
 * @deprecated
 **/
function xarTpl_outputPHPCommentBlockInTemplates()
{
    return xarTpl::outputPHPCommentBlockInTemplates();
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::outputTemplateFilenames()
 * @deprecated
 **/
function xarTpl_outputTemplateFilenames()
{
    return xarTpl::outputTemplateFilenames();
}

/**
 * Legacy call
 *
 * @package core\templating
 * @uses xarTpl::modifyHeaderContent()
 * @deprecated
 **/
function xarTpl_modifyHeaderContent($sourceFileName, &$tplOutput)
{
    return xarTpl::modifyHeaderContent($sourceFileName, $tplOutput);
}

/**
 * Load templates with the .xd extension
 * @deprecated
 */
function xar_legacy_templates_loadsourcefilename($tplBaseDir,$tplSubPart,$tplBase,$templateName,$canTemplateName,$canonical)
{
    xarLog::message("TPL: 7. Try legacy .xd templates in $tplBaseDir/xartemplates/", xarLog::LEVEL_INFO);
    if(!empty($templateName) &&
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xd")) {
    } elseif(
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase.xd")) {
    } elseif($canonical &&
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$canTemplateName.xd")) {
    } else {
        $sourceFileName = '';
    }
    return $sourceFileName;
}

/**
 * Transform entities into numeric
 * Remove $ from varnames in xar:set
 * Add a blanksspace to empty text areas
 * Convert old tag name into new
 * @deprecated
 */
 function xar_legacy_templates_fixLegacy($templatestring)
{
    // Quick & dirty wrapper for missing xmlns:xar in old 1.x templates
    if (!strpos($templatestring, ' xmlns:xar="') && !strpos($templatestring, '</xar:template>')) {
        $templatestring = '<?xml version="1.0" encoding="utf-8"?>
<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">' . $templatestring . '</xar:template>';
        $entities = xar_legacy_templates_entities();

        // Replace non-numeric entitites in old 1.x templates
        $templatestring = str_replace(array_keys($entities),$entities,$templatestring);

        // Allow xar:set name="$var" in old 1.x templates
        $templatestring = str_replace('<xar:set name="$','<xar:set name="',$templatestring);

        // Stop eating </textarea> in old 1.x templates
        $templatestring = str_replace('></textarea>','>&#160;</textarea>',$templatestring);

        // Javascript include in old 1.x templates
        $templatestring = str_replace('<xar:base-include-javascript','<xar:javascript',$templatestring);
    }
    return $templatestring;
}

/**
 * Transform entities into numeric
 * @deprecated
 */
function xar_legacy_templates_entities()
{
    return array(
            '&amp;'    =>      '&#38;',
            '&lsquo;'    =>      '&#145;',
            '&rsquo;'    =>      '&#146;',
            '&ldquo;'    =>      '&#147;',
            '&rdquo;'    =>      '&#148;',
            '&bull'    =>      '&#149;',
            '&en;'    =>      '&#150;',
            '&em;'    =>      '&#151;',

            '&nbsp;'    =>      '&#160;',
            '&iexcl;'    =>      '&#161;',
            '&cent;'    =>      '&#162;',
            '&pound;'    =>      '&#163;',
            '&curren;'    =>      '&#164;',
            '&yen;'    =>      '&#165;',
            '&brvbar;'    =>      '&#166;',
            '&sect;'    =>      '&#167;',
            '&uml;'    =>      '&#168;',
            '&copy;'    =>      '&#169;',
            '&ordf;'    =>      '&#170;',
            '&laquo;'    =>      '&#171;',
            '&not;'    =>      '&#172;',
            '&shy;'    =>      '&#173;',
            '&reg;'    =>      '&#174;',
            '&macr;'    =>      '&#175;',
            '&deg;'    =>      '&#176;',
            '&plusmn;'    =>      '&#177;',
            '&sup2;'    =>      '&#178;',
            '&sup3;'    =>      '&#179;',
            '&acute;'    =>      '&#180;',
            '&micro;'    =>      '&#181;',
            '&para;'    =>      '&#182;',
            '&middot;'    =>      '&#183;',
            '&cedil;'    =>      '&#184;',
            '&sup1;'    =>      '&#185;',
            '&ordm;'    =>      '&#186;',
            '&raquo;'    =>      '&#187;',
            '&frac14;'    =>      '&#188;',
            '&frac12;'    =>      '&#189;',
            '&frac34;'    =>      '&#190;',
            '&iquest;'    =>      '&#191;',
            '&Agrave;'    =>      '&#192;',
            '&Aacute;'    =>      '&#193;',
            '&Acirc;'    =>      '&#194;',
            '&Atilde;'    =>      '&#195;',
            '&Auml;'    =>      '&#196;',
            '&Aring;'    =>      '&#197;',
            '&AElig;'    =>      '&#198;',
            '&Ccedil;'    =>      '&#199;',
            '&Egrave;'    =>      '&#200;',
            '&Eacute;'    =>      '&#201;',
            '&Ecirc;'    =>      '&#202;',
            '&Euml;'    =>      '&#203;',
            '&Igrave;'    =>      '&#204;',
            '&Iacute;'    =>      '&#205;',
            '&Icirc;'    =>      '&#206;',
            '&Iuml;'    =>      '&#207;',
            '&ETH;'    =>      '&#208;',
            '&Ntilde;'    =>      '&#209;',
            '&Ograve;'    =>      '&#210;',
            '&Oacute;'    =>      '&#211;',
            '&Ocirc;'    =>      '&#212;',
            '&Otilde;'    =>      '&#213;',
            '&Ouml;'    =>      '&#214;',
            '&times;'    =>      '&#215;',
            '&Oslash;'    =>      '&#216;',
            '&Ugrave;'    =>      '&#217;',
            '&Uacute;'    =>      '&#218;',
            '&Ucirc;'    =>      '&#219;',
            '&Uuml;'    =>      '&#220;',
            '&Yacute;'    =>      '&#221;',
            '&THORN;'    =>      '&#222;',
            '&szlig;'    =>      '&#223;',
            '&agrave;'    =>      '&#224;',
            '&aacute;'    =>      '&#225;',
            '&acirc;'    =>      '&#226;',
            '&atilde;'    =>      '&#227;',
            '&auml;'    =>      '&#228;',
            '&aring;'    =>      '&#229;',
            '&aelig;'    =>      '&#230;',
            '&ccedil;'    =>      '&#231;',
            '&egrave;'    =>      '&#232;',
            '&eacute;'    =>      '&#233;',
            '&ecirc;'    =>      '&#234;',
            '&euml;'    =>      '&#235;',
            '&igrave;'    =>      '&#236;',
            '&iacute;'    =>      '&#237;',
            '&icirc;'    =>      '&#238;',
            '&iuml;'    =>      '&#239;',
            '&eth;'    =>      '&#240;',
            '&ntilde;'    =>      '&#241;',
            '&ograve;'    =>      '&#242;',
            '&oacute;'    =>      '&#243;',
            '&ocirc;'    =>      '&#244;',
            '&otilde;'    =>      '&#245;',
            '&ouml;'    =>      '&#246;',
            '&divide;'    =>      '&#247;',
            '&oslash;'    =>      '&#248;',
            '&ugrave;'    =>      '&#249;',
            '&uacute;'    =>      '&#250;',
            '&ucirc;'    =>      '&#251;',
            '&uuml;'    =>      '&#252;',
            '&yacute;'    =>      '&#253;',
            '&thorn;'    =>      '&#254;',
            '&yuml;'    =>      '&#255;',
            '&bull;'    =>      '&#8226;',
            );
}
