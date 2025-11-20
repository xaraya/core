<?php

/**
 * Notes:
 * For modules: {tplBase} = {modType}-{funcName}
 * For blocks : {tplBase} = {blockType} or overridden value
 * For props  : {tplBase} = {propertyName} or overridden value

 * Template search order:
 * 1. {theme}/modules/{module}/{tplBase}-{templateName}.xt
 * 2. common/modules/{module}/{tplBase}-{templateName}.xt
 * 3. modules/{module}/xartemplates/{tplBase}-{templateName}.xt
 * 4. {theme}/modules/{module}/{tplBase}.xt
 * 5. common/modules/{module}/{tplBase}.xt
 * 6. modules/{module}/xartemplates/{tplBase}.xt
 * 7. {theme}/modules/{module}/{templateName}.xt (-syntax)
 * 8. common/modules/{module}/{$templateName}.xt (-syntax)
 * 9. modules/{module}/xartemplates/{templateName}.xt (-syntax)
 * 10. complain (later on)
**/

use Xaraya\Services\TemplatingService;
use Xaraya\Services\xar;

/**
 * Exception raised by the templating subsystem
 *
 * @package core\templating
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class BLValidationException extends ValidationExceptions
{
    protected $message = 'A blocklayout tag or attribute construct was invalid, see the tag documentation for the correct syntax';
}

/**
 * Exception raised by the templating subsystem
 *
 * @package core\templating
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class BLException extends xarExceptions
{
    protected $message = 'Unknown blocklayout exception (TODO)';
}

/**
 * BlockLayout Template Engine
 *
 * @package core\templating
 * @category Xaraya Web Applications Framework
 * @version 2.8.6
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @author Andy Varganov <andyv@xaraya.com>
 * @author Jason Judge
 * @deprecated 2.8.6 use xar::tpl() instead
 **/
class xarTpl extends xarObject
{
    protected static ?TemplatingService $tplService = null;

    protected static function tpl(): TemplatingService
    {
        if (!isset(self::$tplService)) {
            $xar = xar::getServicesClass();
            self::$tplService = $xar->tpl();
        }
        return self::$tplService;
    }

    /**
     * Initializes the BlockLayout Template Engine
     *
     * @access public
     * @param array<string, mixed> $args array of parameters<br/>
     *         string  $args[defaultThemeDir] name of the theme to use<br/>
     *         boolean $args[generateXMLURLs] flag to indicate if XML URLs are to be used<br/>
     *         boolean $args[enableTemplatesCaching] flag to indicate if templates should be cached
     * @throws FileNotFoundException
     * @return bool true
     * @todo remove the unnecessary generateXMLURLs arg and static var
    **/
    public static function init(array $args = [])
    {
        // static cache for migration
        self::$tplService = null;
        return self::tpl()->init($args);
    }

    public static function getConfig()
    {
        return self::tpl()->getConfig();
    }

    /**
     * Set base dir
     *
     * Set the base directory for themes, added this for use by
     * the themes module to prevent setting a nonexistent directory
     *
     * @access public
     * @param  string $themesDir
     * @return bool
    **/
    public static function setBaseDir($themesDir)
    {
        return self::tpl()->setBaseDir($themesDir);
    }

    /**
     * Get base dir
     *
     * @access public
     * @return string
    **/
    public static function getBaseDir()
    {
        return self::tpl()->getBaseDir();
    }

    /**
     * Set theme name
     *
     * @access public
     * @param  string $themeName Themename to set
     * @return bool
     * @todo see checkme's
     */
    public static function setThemeName($themeName)
    {
        return self::tpl()->setThemeName($themeName);
    }

    /**
     * Set theme dir
     *
     * @access public
     * @param  string $themeDir
     * @throws DirectoryNotFoundException
     * @return bool
     * @todo   see checkme's
     */
    public static function setThemeDir($themeDir)
    {
        return self::tpl()->setThemeDir($themeDir);
    }

    /**
     * Get theme name for the theme in use.
     *
     * @access public
     * @return string themename
     * @todo   the method_exists / function_exists should be in the xaraya scope, so we can deal with it's oddities
     */
    public static function getThemeName()
    {
        return self::tpl()->getThemeName();
    }

    /**
     * Get theme directory
     *
     * @access public
     * @param  ?string  $theme name of theme, optional, default current theme dir
     * @return string  Theme directory
     */
    public static function getThemeDir($theme = null)
    {
        return self::tpl()->getThemeDir();
    }

    public static function getThemeUrl($theme = null)
    {
        return self::tpl()->getThemeUrl($theme);
    }

    public static function getCodeUrl()
    {
        return self::tpl()->getCodeUrl();
    }

    /**
     * Set page template name
     *
     * @access public
     * @param  string $templateName Name of the page template
     * @return bool
     */
    public static function setPageTemplateName($templateName)
    {
        return self::tpl()->setPageTemplateName($templateName);
    }

    /**
     * Get page template name
     *
     * @access public
     * @return string page template name
     */
    public static function getPageTemplateName()
    {
        return self::tpl()->getPageTemplateName();
    }

    /**
     * Set doctype declared by page template
     *
     * @access public
     * @param  string $doctypeName Identifier string of the doctype
     * @return bool
     */
    public static function setDoctype($doctypeName)
    {
        return self::tpl()->setDoctype($doctypeName);
    }

    /**
     * Get doctype declared by page template
     *
     * @access public
     * @return string doctype identifier
     */
    public static function getDoctype()
    {
        return self::tpl()->getDoctype();
    }

    /**
     * Set page title
     *
     * @access public
     * @param  ?string $title
     * @param  ?string $module
     * @todo   this needs to be moved into the templating domain somehow
     * @return bool
     */
    public static function setPageTitle($title = null, $module = null)
    {
        return self::tpl()->setPageTitle($title, $module);
    }

    /**
     * Get page title
     *
     * @access public
     * @return string
     */
    public static function getPageTitle()
    {
        return self::tpl()->getPageTitle();
    }

    /**
     * Turns module output into a template.
     *
     * @author Paul Rosania <paul@xaraya.com>
     * @author Marco Canini <marco@xaraya.com>
     *
     * @param  string $modName      the module name
     * @param  string $modType      user|admin
     * @param  string $funcName     module function to template
     * @param array<mixed> $tplData arguments for the template
     * @param  ?string $templateName string the specific template to call
     * @throws FileNotFoundException
     * @return string xar::tpl()->executeFromFile($sourceFileName, $tplData)
     */
    public static function module($modName, $modType, $funcName, $tplData = [], $templateName = null)
    {
        return self::tpl()->module($modName, $modType, $funcName, $tplData, $templateName);
    }

    /**
     * Renders a block content through a block template.
     *
     * @author Paul Rosania <paul@xaraya.com>
     * @author Marco Canini <marco@xaraya.com>
     *
     * @param  string $modName   the module name
     * @param  string $blockType the block type (xar_block_types.type)
     * @param array<mixed> $tplData arguments for the template
     * @param  ?string $tplName   the specific template to call
     * @param  ?string $tplBase   the base name of the template (defaults to $blockType)
     * @throws FileNotFoundException
     * @return string xar::tpl()->executeFromFile($sourceFileName, $tplData)
     */
    public static function block($modName, $blockType, $tplData = [], $tplName = null, $tplBase = null, $tplModule = null)
    {
        return self::tpl()->block($modName, $blockType, $tplData, $tplName, $tplBase, $tplModule);
    }

    /**
     * Render a DD object through a template
     * @access public
     * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
     * @param  string $objectName   the name of the object type, or some other name specified in BL tag or API call
     * @param  string $tplType      the template type to render
     *                              objects   : ( showdisplay(default)|showview|showform|showlist )
     * @param array<mixed> $tplData arguments for the template
     * @param  ?string $tplBase      the template type can be overridden too ( unused )
     * @throws FileNotFoundException
     * @return string xar::tpl()->executeFromFile($sourceFileName, $tplData)
     */
    public static function object($modName, $objectName, $tplType = 'showdisplay', $tplData = [], $tplBase = null)
    {
        return self::tpl()->object($modName, $objectName, $tplType, $tplData);
    }

    /**
     * Render a DD property through a template
     *
     * @access public
     * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
     * @param  string $propertyName  the name of the property type, or some other name specified in BL tag or API call
     * @param  string $tplType      the template type to render
     *                              properties: ( showoutput(default)|showinput|showhidden|validation|label )
     * @param array<mixed> $tplData arguments for the template
     * @param  ?string $tplBase      the template type can be overridden too ( used by xar:data-label - why not change tplType? )
     * @throws FileNotFoundException
     * @return string xar::tpl()->executeFromFile($sourceFileName, $tplData)
     */
    public static function property($modName, $propertyName, $tplType = 'showoutput', $tplData = [], $tplBase = null)
    {
        return self::tpl()->property($modName, $propertyName, $tplType, $tplData, $tplBase);
    }

    /**
     * Get theme template image replacement for a module's image
     *
     * Example:
     * $my_module_image = xar::tpl()->getImage('button1.png');
     * $other_module_image = xar::tpl()->getImage('set1/info.png','module');
     *
     * Correct practices:
     *
     * 1. module developers should never rely on theme's images, but instead
     * provide their own artwork inside modules/<module>/xarimages/ directory
     * and use this function to reference their images in the module's functions.
     * Such reference can then be safely passed to the module template.
     *
     * 2. theme developers should always check for the modules images
     * (at least for all core modules) and provide replacements images
     * inside the corresponding themes/<theme>/modules/<module>/images/
     * directories as necessary
     *
     * Note : your module is still responsible for taking care that "images"
     *        don't contain nasty stuff. Filter as appropriate when using
     *        this function to generate image URLs...
     *
     * @author  Andy Varganov <andyv@xaraya.com>
     *
     * @param   string $fileName
     * @param   ?string $scope
     * @param   ?string $package
     * @return  string|null image url if it exists or module image url if not, or NULL if neither found
     *
     * @todo    provide examples, improve description, add functionality
     * @todo    provide XML URL override flag
     * @todo    XML encode absolute URIs too?
     * @todo    <chris/> Rewrite the above and document correct practice/examples elsewhere
    */
    public static function getImage($fileName, $scope = null, $package = null)
    {
        return self::tpl()->getImage($fileName, $scope, $package);
    }

    /**
     * Get theme/module/property/block file with the right file URL - for non-standard elements
     *
     * Example:
     * $my_module_file = xar::tpl()->getFile('xardata/config-sample.xml', 'module', 'dynamicdata');
     *
     * Note : your module is still responsible for taking care that "files"
     *        don't contain nasty stuff. Filter as appropriate when using
     *        this function to generate file URLs...
     *
     * @param   string $fileName the fileName relative to the theme/module/property/block folder/
     * @param   ?string $scope the scope to check for (theme/module/property/block)
     * @param   ?string $package the actual theme/module/property/block we're looking at
     * @return  string|null file url if it exists or NULL if not
    */
    public static function getFile($fileName, $scope = null, $package = null)
    {
        return self::tpl()->getFile($fileName, $scope, $package);
    }

    /**
     * Execute a pre-compiled template string with the supplied template variables
     *
     * @access public
     * @param  string $templateCode pre-compiled template code (see xar::tpl()->compileString)
     * @param array<mixed> $tplData template variables
     * @return string filled-in template
     * @todo   this is not MLS-aware (never was)
     * @todo   how 'special' should the 'memory' file be, namewise?
     */
    public static function string($templateCode, $tplData)
    {
        return self::tpl()->string($templateCode, $tplData);
    }

    /**
     * Execute a specific template file with the supplied template variables
     *
     * @access public
     * @param  string $fileName location of the template file
     * @param array<mixed> $tplData template variables
     * @return string filled-in template
     */
    public static function file($fileName, &$tplData)
    {
        return self::tpl()->file($fileName, $tplData);
    }

    /**
     * Compile a template string for storage and/or later use in xar::tpl()->string()
     * Note : your module should always support the possibility of re-compiling
     *        template strings e.g. after an upgrade, so you should store both
     *        the original template and the compiled version if necessary
     *
     * @access public
     * @param  string $templateSource template source
     * @return string compiled template
     */
    public static function compileString($templateSource)
    {
        return self::tpl()->compileString($templateSource);
    }

    /**
     * Renders a page template.
     *
     * @author Paul Rosania <paul@xaraya.com>
     * @author Marco Canini <marco@xaraya.com>
     *
     * @param  string $mainModuleOutput       the module output
     * @param  ?string $pageTemplate           the page template to use (without extension .xt)
     * @return string
     *
     * @todo Needs a rewrite, i.e. finalisation of tplOrder scenario
     */
    public static function renderPage($mainModuleOutput, $pageTemplate = null, $context = null)
    {
        return self::tpl()->renderPage($mainModuleOutput, $pageTemplate);
    }

    /**
     * Render a block box
     *
     * @access public
     * @param array<string, mixed> $blockInfo  Information on the block
     * @param  ?string $templateName string
     * @return string xar::tpl()->executeFromFile($sourceFileName, $blockInfo)
     *
     * @todo the search logic for the templates can perhaps use the private function?
     * @todo implement common templates in cascade
     */
    public static function renderBlockBox($blockInfo, $templateName = null)
    {
        return self::tpl()->renderBlockBox($blockInfo, $templateName);
    }

    /**
     * xar:template tag handler
     * Include a subtemplate from wherever
     *
     * @access public
     * @param  string $tplType      scope in which to look for templates [theme|module|block|property]
     * @param  string $package      name of the theme|module|block|property supplying the template
     * @param  string $tplName      The name of the template to use
     * @param array<mixed> $tplData array of data for the template
     * @param  string $tplPart      Optional sub path to look for templates in relative to template path
     * @param  ?string $callerMod    Optional name of the module calling the template, if different from $package
     * @throws FileNotFoundException
     * @return string self::executeFromFile($sourceFileName, $tplData);
    **/
    public static function includeTemplate($tplType, $package, $tplBase, $tplData = [], $tplPart = 'includes', $tplName = null, $callerMod = null)
    {
        return self::tpl()->includeTemplate($tplType, $package, $tplBase, $tplData, $tplPart, $tplName, $callerMod);
    }

    /* PRIVATE FUNCTIONS */

    /**
     * Execute template from file
     *
     * FIXME: this cannot be private since it's used by the mail module
     * @access private
     * @param  string $sourceFileName       From which file do we want to execute? Assume it exists by now ;-)
     * @param array<mixed> $tplData Template variables
     * @param  string $tplType              'module' or 'page'
     * @return string generated output from the file
     * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
     * @todo  make the checking whether templatecode is set more robust (related to templated exception handling)
     */
    public static function executeFromFile($sourceFileName, $tplData, $tplType = 'module')
    {
        return self::tpl()->executeFromFile($sourceFileName, $tplData, $tplType);
    }



    /* END PRIVATE FUNCTIONS */


    /**
     * Output template
     *
     * @access public
     * @param  string $sourceFileName
     * @param  string $tplOutput
     * @return string generated output from the template
     *
     * @todo Rethink this function, it contains hardcoded xhtml
     */
    public static function outputTemplate($sourceFileName, &$tplOutput)
    {
        return self::tpl()->outputTemplate($sourceFileName, $tplOutput);
    }

    /**
     * Output php comment block in templates
     *
     * @access public
     * @return int value of xar::tpl()->showPHPCommentBlockInTemplates (0 or 1)
     */
    public static function outputPHPCommentBlockInTemplates()
    {
        return self::tpl()->outputPHPCommentBlockInTemplates();
    }

    /**
     * Output template filenames
     *
     * @access public
     * @return int value of xar::tpl()->showTemplateFilenames (0 or 1)
     *
     * @todo Check whether the check for xar::mod()->getVar is needed
     * @todo Rethink this function
     */
    public static function outputTemplateFilenames()
    {
        return self::tpl()->outputTemplateFilenames();
    }
}
