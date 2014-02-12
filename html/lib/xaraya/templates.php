<?php
/**
 * BlockLayout Template Engine
 *
 * @package core
 * @package templating
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @author Andy Varganov <andyv@xaraya.com>
 * @author Jason Judge
 **/

/**
 * Exceptions for this subsystem
 *
**/
class BLValidationException extends ValidationExceptions
{
    protected $message = 'A blocklayout tag or attribute construct was invalid, see the tag documentation for the correct syntax';
}

class BLException extends xarExceptions
{
    protected $message = 'Unknown blocklayout exception (TODO)';
}

sys::import('xaraya.variables.config');

class xarTpl extends Object
{
    // statics to replace $GLOBALS[xarTpl_*]
    protected static $themeName;
    protected static $themeDir;
    
    protected static $generateXMLURLs  = true;
    protected static $doctype          = '';
    protected static $pageTemplateName;
    protected static $pageTitle;
    
    protected static $showPHPCommentBlockInTemplates;
    protected static $showTemplateFilenames;

/**
 * Initializes the BlockLayout Template Engine
 *
 * @access public
 * @param  array   $args array of parameters<br/>
 *         string  $args[defaultThemeDir] name of the theme to use<br/>
 *         boolean $args[generateXMLURLs] flag to indicate if XML URLs are to be used<br/>
 *         boolean $args[enableTemplatesCaching] flag to indicate if templates should be cached
 * @throws FileNotFoundException
 * @return boolean true
 * @todo remove the unnecessary generateXMLURLs arg and static var
**/
    public static function init(&$args)
    {
        // This is the theme directory, solo (aka, themename)
        self::setThemeDir($args['defaultThemeDir']);
        
        // This should be always true or better defined if it's 
        // a client thing (js internal code generation for example)
        self::$generateXMLURLs = $args['generateXMLURLs'];

        if (!self::setPageTemplateName('default')) {
            // If there is no page template, we can't show anything
            throw new FileNotFoundException('default.xt',"xarTpl::init: Nonexistent #(1) page in theme directory '". self::getThemeDir() ."'");
        }

        // @todo is the core define still needed now?
        sys::import('xaraya.caching.template');
        xarTemplateCache::init(sys::varpath() . XARCORE_TPL_CACHEDIR, $args['enableTemplatesCaching']);

        // This is wrong here as well, but it's better at least than in modules.php
        sys::import('xaraya.themes');
        return true;
    }

/**
 * Set base dir
 *
 * Set the base directory for themes, added this for use by
 * the themes module to prevent setting a nonexistent directory
 * 
 * @access public
 * @param  string themesDir
 * @return boolean
**/    
    public static function setBaseDir($themesDir)
    {
        assert('$themesDir != "" && $themesDir{0} != "/"');
        if (!is_dir($themesDir)) {
            // no directory
            throw new DirectoryNotFoundException($themesDir, 'xarTpl::setBaseDir: Nonexistent base themes directory #(1)');
        } elseif (!is_dir($themesDir . '/' . self::getThemeName())) {
            // found a directory, but the current theme isn't in it
            throw new DirectoryNotFoundException(array(self::getThemeName(), $themesDir), 'xarTpl::setBaseDir: Nonexistant theme #(1) in base themes directory #(2)');
        }
        xarConfigVars::set(null, 'Site.BL.ThemesDirectory', $themesDir);
        return true;     
    }

/**
 * Get base dir
 *
 * @access public
 * @params  none
 * @return string
**/
    public static function getBaseDir()
    {
        return sys::web() . xarConfigVars::get(null, 'Site.BL.ThemesDirectory', 'themes');
    }

/**
 * Set theme name
 *
 * @access public
 * @param  string $themeName Themename to set
 * @return boolean
 * @todo see checkme's
 */
    public static function setThemeName($themeName)
    {
        assert('$themeName != "" && $themeName{0} != "/"');
        $currentBase = self::getBaseDir();
        if (!is_dir($currentBase . '/'.$themeName)) {
            // @checkme: return false here vs throw exception in setThemeDir ?
            return false;
        }
        self::setThemeNameAndDir($themeName);
        return true;
    }

/**
 * Set theme dir
 *
 * @access public
 * @param  string themeDir
 * @throws DirectoryNotFoundException
 * @return boolean
 * @todo   see checkme's
 */
    public static function setThemeDir($themeDir)
    {
        assert('$themeDir != "" && $themeDir{0} != "/"');
        $currentBase = self::getBaseDir();
        if (is_dir($currentBase . '/' . $themeDir)) {
            // use current
        } elseif (is_dir($currentBase . '/common')) {
            // fall back to common
            $themeDir = 'common';
        } else {
            // @checkme: throw exception here vs return false in setThemeName ?
            throw new DirectoryNotFoundException("$currentBase/$themeDir", 'xarTpl::setThemeDir: Nonexistent theme directory #(1)');
        }
        self::setThemeNameAndDir($themeDir);
        return true;   
    }

/**
 * Private helper function for xarTpl::setThemeName and xarTpl::setThemeDir
 *
 * @access private
 * @param  string $name Name of the theme
 * @todo theme name and dir are not required to be identical
 * @return void
 */
    private static function setThemeNameAndDir($name)
    {
        // dir and name are still required to be the same
        self::$themeName = $name;
        self::$themeDir  = self::getBaseDir() . '/' . $name;
    }

/**
 * Get theme name for the theme in use.
 *
 * @access public
 * @params none
 * @return string themename
 * @todo   the method_exists / function_exists should be in the xaraya scope, so we can deal with it's oddities
 */
    public static function getThemeName()
    {
        if (isset(self::$themeName))
            return self::$themeName;
        // If it is not set, set it return the default theme.
        // @checkme: modules is a depency of templates, redundant check?
        if (method_exists('xarModVars', 'get')) {
            $themeName = xarModVars::get('themes', 'default_theme');
            if (!empty($themeName))
                self::setThemeName($themeName);
        }
        assert('isset(self::$themeName); /* Theme name could not be set properly */');
        return self::$themeName;
    }

/**
 * Get theme directory
 *
 * @access public
 * @param  string  name of theme, optional, default current theme dir
 * @return string  Theme directory
 */
    public static function getThemeDir($theme=null)
    {
        $currentBase = self::getBaseDir();
        if (isset($theme) && is_dir($currentBase . '/' . $theme))
            return $currentBase . '/' . $theme;
        return self::$themeDir;
    }

/**
 * Set page template name
 *
 * @access public
 * @param  string $templateName Name of the page template
 * @return boolean
 */
    public static function setPageTemplateName($templateName)
    {
        assert('$templateName != ""');
        if (!self::exists('theme', self::getThemeName(), $templateName, null, 'pages'))
            return false;
        self::$pageTemplateName = $templateName;
        return true;
    }

/**
 * Get page template name
 *
 * @access public
 * @params none
 * @return string page template name
 */
    public static function getPageTemplateName()
    {
        return self::$pageTemplateName;
    }

/**
 * Set doctype declared by page template
 *
 * @access public
 * @param  string $doctypeName Identifier string of the doctype
 * @return boolean
 */
    public static function setDoctype($doctypeName)
    {
        assert('is_string($doctypeName); /* doctype should always be a string */');
        self::$doctype = $doctypeName;    
        return true;
    }

/**
 * Get doctype declared by page template
 *
 * @access public
 * @params none
 * @return string doctype identifier
 */
    public static function getDoctype()
    {
        return self::$doctype;
    }

/**
 * Set page title
 *
 * @access public
 * @param  string $title
 * @param  string $module
 * @todo   this needs to be moved into the templating domain somehow
 * @return boolean
 */
    public static function setPageTitle($title = NULL, $module = NULL)
    {
        // keep track of page title when we're caching
        xarCache::setPageTitle($title, $module);

        xarLogMessage("TPL: Setting pagetitle to $title");
        // @checkme: modules is a depency of templates, redundant check?
        if (!method_exists('xarModVars','Get')){
            self::$pageTitle = $title;
        } else {
            $order      = xarModVars::get('themes', 'SiteTitleOrder');
            $separator  = xarModVars::get('themes', 'SiteTitleSeparator');
            if (empty($module)) {
                // FIXME: the ucwords is layout stuff which doesn't belong here
                // <chris/> Why don't we just use display name then?
                $module = ucwords(xarMod::getDisplayName());
            }
            switch(strtolower($order)) {
                case 'default':
                default:
                    self::$pageTitle = xarModVars::get('themes', 'SiteName') . $separator . $module . $separator . $title;
                break;
                case 'sp':
                    self::$pageTitle = xarModVars::get('themes', 'SiteName') . $separator . $title;
                break;
                case 'mps':
                    self::$pageTitle = $module . $separator . $title . $separator .  xarModVars::get('themes', 'SiteName');
                break;
                case 'pms':
                    self::$pageTitle = $title . $separator .  $module . $separator . xarModVars::get('themes', 'SiteName');
                break;
                case 'to':
                    self::$pageTitle = $title;
                break;
            }
        }
        return true;
    }

/**
 * Get page title
 *
 * @access public
 * @params none
 * @return string
 */
    public static function getPageTitle()
    {
        if (isset(self::$pageTitle))
            return self::$pageTitle;
        return '';
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
 * @param  array  $tplData      arguments for the template
 * @param  string $templateName string the specific template to call
 * @throws FileNotFoundException
 * @return string xarTpl::executeFromFile($sourceFileName, $tplData)
 */
    public static function module($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
    {
        // Basename of module template is apitype-functioname
        $tplBase        = "$modType-$funcName";

        // Get the right source filename (current > common > module)
        $sourceFileName = self::getScopeFileName('module', $modName, $tplBase, $templateName);

        // Common data for BL
        $tplData['_bl_module_name'] = $modName;
        $tplData['_bl_module_type'] = $modType;
        $tplData['_bl_module_func'] = $funcName;
        $tplData['_bl_template']    = $sourceFileName;
        $tpl = (object) null;
        $tpl->pageTitle = self::getPageTitle();
        $tplData['tpl'] = $tpl;


        // TODO: make this work different, for example:
        // 1. Only create a link somewhere on the page, 
        //    when clicked opens a page with the variables on that page
        // 2. Create a page in the themes module with an interface
        // 3. Use 1. to link to 2.
        // @checkme: modules is a depency of templates, redundant check?
        if (method_exists('xarModVars','Get') && function_exists('xarUserGetVar')) {
            if (xarModVars::get('themes', 'variable_dump') &&
                in_array(xarUserGetVar('uname'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                echo '<pre>',var_dump($tplData),'</pre>';
            }
        } 

        if (empty($sourceFileName)) {
            throw new FileNotFoundException("Module: [$modName],[$tplBase],[$templateName]");
        }
        return self::executeFromFile($sourceFileName, $tplData);
    }

/**
 * Renders a block content through a block template.
 *
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * 
 * @param  string $modName   the module name
 * @param  string $blockType the block type (xar_block_types.type)
 * @param  array  $tplData   arguments for the template
 * @param  string $tplName   the specific template to call
 * @param  string $tplBase   the base name of the template (defaults to $blockType)
 * @throws FileNotFoundException
 * @return string xarTpl::executeFromFile($sourceFileName, $tplData)
 */
    public static function block($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL, $tplModule = NULL)
    {
        // use name of blocktype as base unless over-ridden
        $tplBase = empty($tplBase) ? $blockType : $tplBase;
        if (!empty($modName)) {
            // get module block template (current > common > module)
            $sourceFileName = self::getScopeFileName('module', $modName, $tplBase, $tplName, 'blocks');
        } else {
            // get standalone block template (current > common > module override > block)
            $sourceFileName = self::getScopeFileName('block', $blockType, $tplBase, $tplName, '', $tplModule);
        }
        if (empty($sourceFileName)) {
            $msg = "Block: [#(1)],[#(2)],[#(3)]";
            $vars = array($modName, $tplBase, $tplName);
            throw new FileNotFoundException($vars, $msg);
        }
        return self::executeFromFile($sourceFileName, $tplData);

    }

    public static function exists($scope, $package, $tplBase, $tplName=null, $tplPart='')
    {
        return (bool) self::getScopeFileName($scope, $package, $tplBase, $tplName, $tplPart);
    }

/**
 * Determine the template sourcefile to use
 *
 * Based on the scope, the module|property|block|theme name, the basename for the template
 * a possible override and a subpart and the active
 * theme, determine the template source we should use and loads
 * the appropriate translations based on the outcome (see todo).
 *
 * @access private
 * @param  string $scope        scope in which to look for templates [theme|module|block|property]
 * @param  string $package      name of the theme|module|block|property supplying the template
 * @param  string $tplBase      The base name for the template
 * @param  string $tplName      The name of the template to use, if any
 * @param  string $tplPart      Optional sub path to look for templates in, default ''
 * @param  string $callerMod    Optional name of module calling this package (looks here first if supplied)
 * @return string the path [including sys::code()] to an existing template sourcefile, or empty
 *
 * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction) 
 */

    private static function getScopeFileName($scope, $package, $tplBase, $tplName=null, $tplPart='', $callerMod=null)
    {
        // prep input
        $package = xarVarPrepForOS($package);
        $tplBase = xarVarPrepForOS($tplBase);
        if (!empty($tplName))
            $tplName = xarVarPrepForOS($tplName);
        if (!empty($tplPart))
            $tplPart = strtr(trim(xarVarPrepForOS($tplPart)), " ", "/");
        $canTemplateName = strtr($tplName, "-", "/");
        $canonical = ($canTemplateName == $tplName) ? false : true;

        $cachename = "$scope:$package:$tplBase:$tplName:$tplPart:$callerMod";
        // cache frequently-used sourcefilenames 
        if (xarCoreCache::isCached('Templates.Element', $cachename))
            return xarCoreCache::getCached('Templates.Element', $cachename);

        // default paths 
        $themePath = self::getThemeDir();
        $commonPath = self::getThemeDir('common');
        $codePath = sys::code();

        if ($scope == 'theme') {
            // theme scope
            // if package isn't current theme or common theme, look there first
            if ($package != self::getThemeName() && $package != 'common')
                $basepaths[] = self::getThemeDir($package);
            $basepaths[] = $themePath;
            $basepaths[] = $commonPath;
        } else {

            switch ($scope) {
                case 'module':
                    $packages = 'modules';           
                break;
                case 'block':
                    // standalone blocks
                    $packages = 'blocks';
                break;
                case 'property':
                    // standalone properties        
                    $packages = 'properties';
                break;
                default:
                    $vars = array($scope);
                    $msg = 'Invalid scope "#(1)" for core function xarTpl::getScopeFileName()';
                    throw new BadParameterException($vars, $msg);
                break;
            }
            if (!empty($callerMod) && $callerMod != $package) {                
                if ($scope != 'module') {
                    $basepaths = array(
                        "$themePath/modules/$callerMod/$packages/",
                        "$commonPath/modules/$callerMod/$packages/",
                        "{$codePath}modules/$callerMod/xartemplates/$packages/",
                        "$themePath/modules/$callerMod/",
                        "$commonPath/modules/$callerMod/",
                        "{$codePath}modules/$callerMod/xartemplates/",
                        "$themePath/$packages/$package/",
                        "$commonPath/$packages/$package/",
                        "{$codePath}{$packages}/$package/xartemplates/",
                    );
                } else {               
                    $basepaths = array(
                        "$themePath/modules/$callerMod/",
                        "$commonPath/modules/$callerMod/",
                        "{$codePath}modules/$callerMod/xartemplates/",
                        "$themePath/$packages/$package/",
                        "$commonPath/$packages/$package/",
                        "{$codePath}{$packages}/$package/xartemplates/",
                    );
                }                         
            } else {
                $basepaths = array(
                    "$themePath/$packages/$package/",
                    "$commonPath/$packages/$package/",
                    "{$codePath}{$packages}/$package/xartemplates/",
                );
            }  

        }
        
        $paths = array();
        // approach this the other way, look for tplBase-tplName in all paths first
        if (!empty($tplName)) {
            foreach ($basepaths as $basepath)
                $paths[] = "$basepath/$tplPart/$tplBase-$tplName.xt";
        }
        // then look for tplBase... (checkme: this is cfr getSourceFileName order)
        foreach ($basepaths as $basepath) 
            $paths[] = "$basepath/$tplPart/$tplBase.xt";
        // then look at canonical... (see checkme)
        if ($canonical) {
            foreach ($basepaths as $basepath)
                $paths[] = "$basepath/$tplPart/$canTemplateName.xt";
        }

        $sourceFileName = '';
        if (!empty($paths)) {
            foreach ($paths as $file) {
                if (!file_exists($file)) continue;
                $sourceFileName = $file;
                break;
            }
        }
        // Some parts may have been empty, remove extra slashes
        $sourceFileName = preg_replace('%\/\/+%','/',$sourceFileName);

        xarCoreCache::setCached('Templates.Element', $cachename, $sourceFileName);

        return $sourceFileName;
       
    }

/**
 * Determine the template sourcefile to use
 * @NOTE: this method is no longer used and is targetted for removal
 *
 * Based on the module, the basename for the template
 * a possible overribe and a subpart and the active
 * theme, determine the template source we should use and loads
 * the appropriate translations based on the outcome.
 *
 * @access private
 * @param  string $modName      Module name doing the request
 * @param  string $tplBase      The base name for the template
 * @param  string $templateName The name for the template to use if any
 * @param  string $tplSubPart   A subpart ('' or 'blocks' or 'properties')
 * @return string the path [including sys::code()] to an existing template sourcefile, or empty
 *
 * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
 * @todo implement common templates in cascade 
 */
/*    private static function getSourceFileName($modName,$tplBase, $templateName = NULL, $tplSubPart = '')
    {
        if(method_exists('xarMod','getBaseInfo')) {
            if(!($modBaseInfo = xarMod::getBaseInfo($modName))) return;
            $modOsDir = $modBaseInfo['osdirectory'];
        } elseif(!empty($modName)) {
            $modOsDir = $modName;
        }

        // For modules: {tplBase} = {modType}-{funcName}
        // For blocks : {tplBase} = {blockType} or overridden value
        // For props  : {tplBase} = {propertyName} or overridden value

        // Template search order:
        // 1. {theme}/modules/{module}/{tplBase}-{templateName}.xt
        // 2. common/modules/{module}/{tplBase}-{templateName}.xt
        // 3. modules/{module}/xartemplates/{tplBase}-{templateName}.xt
        // 4. {theme}/modules/{module}/{tplBase}.xt
        // 5. common/modules/{module}/{tplBase}.xt
        // 6. modules/{module}/xartemplates/{tplBase}.xt
        // 7. {theme}/modules/{module}/{templateName}.xt (-syntax)
        // 8. common/modules/{module}/{$templateName}.xt (-syntax)
        // 9. modules/{module}/xartemplates/{templateName}.xt (-syntax)
        // 10. complain (later on)

        $tplThemesDir = self::getThemeDir();
        $tplCommonDir = self::getThemeDir('common');
        $tplBaseDir   = sys::code() . "modules/$modOsDir";

        $canTemplateName = strtr($templateName, "-", "/");
        $canonical = ($canTemplateName == $templateName) ? false : true;

        if (!empty($templateName)) {
            xarLogMessage("TPL: 1. $tplThemesDir/modules/$modOsDir/$tplSubPart/$tplBase-$templateName.xt");
            xarLogMessage("TPL: 2. $tplCommonDir/modules/$modOsDir/$tplSubPart/$tplBase-$templateName.xt");
            xarLogMessage("TPL: 3. $tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xt");
        }
        xarLogMessage("TPL: 4. $tplThemesDir/modules/$modOsDir/$tplSubPart/$tplBase.xt");
        xarLogMessage("TPL: 5. $tplCommonDir/modules/$modOsDir/$tplSubPart/$tplBase.xt");
        xarLogMessage("TPL: 6. $tplBaseDir/xartemplates/$tplSubPart/$tplBase.xt");
        if ($canonical) {
            xarLogMessage("TPL: 7. $tplThemesDir/modules/$modOsDir/$tplSubPart/$canTemplateName.xt");
            xarLogMessage("TPL: 8. $tplCommonDir/modules/$modOsDir/$tplSubPart/$canTemplateName.xt");
            xarLogMessage("TPL: 9. $tplBaseDir/xartemplates/$tplSubPart/$canTemplateName.xt");
        }
        
        // TPL 1: Current theme (module)
        if (!empty($templateName) &&
            file_exists($sourceFileName = "$tplThemesDir/modules/$modOsDir/$tplSubPart/$tplBase-$templateName.xt")) {
        // TPL 1: Current theme (property) 
        // @FIXME this path is all wrong
        } elseif (!empty($templateName) &&
            file_exists($sourceFileName = "$tplThemesDir/properties/$templateName/templates/$tplBase.xt")){
        // TPL 2: common (module)
        } elseif (!empty($templateName) &&
            file_exists($sourceFileName = "$tplCommonDir/modules/$modOsDir/$tplSubPart/$tplBase-$templateName.xt")) {
        // TPL 2: common (property) 
        // @FIXME this path is all wrong
        } elseif (!empty($templateName) &&
            file_exists($sourceFileName = "$tplCommonDir/properties/$templateName/templates/$tplBase.xt")){
        // TPL 3: (module)
        } elseif(!empty($templateName) &&
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xt")){
        // TPL 3: (property)
        } elseif(!empty($templateName) &&
            file_exists($sourceFileName = sys::code() . "properties/$templateName/xartemplates/$tplBase.xt")) {
        // TPL 4: Current theme (module)
        } elseif(
            file_exists($sourceFileName = "$tplThemesDir/modules/$modOsDir/$tplSubPart/$tplBase.xt")) {
        // TPL 5: common (module)
        } elseif(
            file_exists($sourceFileName = "$tplCommonDir/modules/$modOsDir/$tplSubPart/$tplBase.xt")) {
        // TPL 6: (module)
        } elseif(
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase.xt")) {
        // TPL 7: Current theme (module)
        } elseif($canonical &&
            file_exists($sourceFileName = "$tplThemesDir/modules/$modOsDir/$tplSubPart/$canTemplateName.xt")) {
        // TPL 8: common (module)
        } elseif($canonical &&
            file_exists($sourceFileName = "$tplCommonDir/modules/$modOsDir/$tplSubPart/$canTemplateName.xt")) {
        // TPL 9: (module)        
        } elseif($canonical &&
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$canTemplateName.xt")) {
        // Legacy
        } elseif (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true) {
            try {
                sys::import('xaraya.legacy.templates');
                $sourceFileName = loadsourcefilename($tplBaseDir,$tplSubPart,$tplBase,$templateName,$canTemplateName,$canonical);
            } catch (Exception $e) {$sourceFileName = '';}
        } else {
            // let functions higher up worry about what to do, e.g. DD object of property fallback template
            $sourceFileName = '';
        }        

        // Some parts may have been empty, remove extra slashes
        $sourceFileName = preg_replace('%\/\/+%','/',$sourceFileName);

        return $sourceFileName;
    }*/

/**
 * Render a DD object through a template
 * @access public
 * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
 * @param  string $objectName   the name of the object type, or some other name specified in BL tag or API call
 * @param  string $tplType      the template type to render
 *                              objects   : ( showdisplay(default)|showview|showform|showlist )
 * @param  array  $tplData      arguments for the template
 * @param  string $tplBase      the template type can be overridden too ( unused )
 * @throws FileNotFoundException
 * @return string xarTpl::executeFromFile($sourceFileName, $tplData)
 */
    public static function object($modName, $objectName, $tplType = 'showdisplay', $tplData = array(), $tplBase = NULL)
    {
        $modName = xarVarPrepForOS($modName);
        $objectName = xarVarPrepForOS($objectName);
        $tplType = xarVarPrepForOS($tplType);
        $tplBase   = empty($tplBase) ? $tplType : xarVarPrepForOS($tplBase);
        $cachename = "$modName:$objectName:$tplType:$tplBase:objects";

        // cache frequently-used sourcefilenames for DD elements
        if (xarCoreCache::isCached('Templates.DDElement', $cachename)) {
            $sourceFileName = xarCoreCache::getCached('Templates.DDElement', $cachename);
            return self::executeFromFile($sourceFileName, $tplData);
        }
        
        $sourceFileName = self::getScopeFileName('module', $modName, $tplBase, $objectName, 'objects');
        if (empty($sourceFileName) && $modName != 'dynamicdata')
            $sourceFileName = self::getScopeFileName('module', 'dynamicdata', $tplBase, $objectName, 'objects');

        if (empty($sourceFileName)) 
            throw new FileNotFoundException("DD Element: [$modName],[$tplBase],[$objectName]");
        
        xarCoreCache::setCached('Templates.DDElement', $cachename, $sourceFileName);
        
        return self::executeFromFile($sourceFileName, $tplData);
    }

/**
 * Render a DD property through a template
 *
 * @access public
 * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
 * @param  string $propertyName  the name of the property type, or some other name specified in BL tag or API call
 * @param  string $tplType      the template type to render
 *                              properties: ( showoutput(default)|showinput|showhidden|validation|label )
 * @param  array  $tplData      arguments for the template
 * @param  string $tplBase      the template type can be overridden too ( unused )
 * @throws FileNotFoundException
 * @return string xarTpl::executeFromFile($sourceFileName, $tplData)
 */
    public static function property($modName, $propertyName, $tplType = 'showoutput', $tplData = array(), $tplBase = NULL)
    {
        $modName = xarVarPrepForOS($modName);
        $propertyName = xarVarPrepForOS($propertyName);
        $tplType = xarVarPrepForOS($tplType);
        $tplBase   = empty($tplBase) ? $tplType : xarVarPrepForOS($tplBase);
        $cachename = "$modName:$propertyName:$tplType:$tplBase:properties";

        // cache frequently-used sourcefilenames for DD elements
        if (xarCoreCache::isCached('Templates.DDElement', $cachename)) {
            $sourceFileName = xarCoreCache::getCached('Templates.DDElement', $cachename);
            return self::executeFromFile($sourceFileName, $tplData);
        }

        $sourceFileName = self::getScopeFileName('module', $modName, $tplBase, $propertyName, 'properties');

        // Property fall-back to default template in the module the property belongs to
        if (empty($sourceFileName)) {
            $tplModule = DataPropertyMaster::getProperty(array('type' => $propertyName))->tplmodule; 
            
            if ($modName == 'auto') {
                // standalone property called in standalone context 
                $sourceFileName = self::getScopeFileName('property', $propertyName, $tplBase, $propertyName);
                
                if (empty($sourceFileName)) {
                    // property inherits its template
                    $sourceFileName = self::getScopeFileName('module', $tplModule, $tplBase, $propertyName, 'properties', $modName);
                }
            } else {
                // property called in module context
                if ($tplModule == 'auto') {
                    // standalone property (caller > owner) 
                    $sourceFileName = self::getScopeFileName('property', $propertyName, $tplBase, $propertyName, '', $modName);
                } else {
                    // module property (caller > owner)
                    $sourceFileName = self::getScopeFileName('module', $tplModule, $tplBase, $propertyName, 'properties', $modName);
    
                }
            }
        }

        // fall back on dynamicdata template
        if (empty($sourceFileName))
            $sourceFileName = self::getScopeFileName('module', 'dynamicdata', $tplBase, $propertyName, 'properties');

        if (empty($sourceFileName)) 
            throw new FileNotFoundException("DD Element: [$modName],[$tplBase],[$propertyName]");
        
        xarCoreCache::setCached('Templates.DDElement', $cachename, $sourceFileName);
        
        return self::executeFromFile($sourceFileName, $tplData);
/*        
        // default paths 
        $themePath = self::getThemeDir();
        $commonPath = self::getThemeDir('common');
        $codePath = sys::code();
        
        $tplModule = DataPropertyMaster::getProperty(array('type' => $propertyName))->tplmodule;
        $tplName = "$tplBase-$propertyName";  

        // handle template cascade (current > common > code) 
        $themepaths = array($themePath, $commonPath);
        // look in themes (current > common)
        foreach ($themepaths as $basepath) {
            // handle property cascade (caller > owner > dynamicdata)
            if ($modName == 'auto') {
                // standalone property, called in standalone context (owner)
                $paths[] = "$basepath/properties/$propertyName";
            } else {
                // property called in module context
                if ($tplModule == 'auto') {
                    // standalone property (caller) 
                    $paths[] = "$basepath/modules/$modName/properties";
                    // standalone property (owner)
                    $paths[] = "$basepath/properties/$propertyName";
                } else {
                    // module property (caller)
                    $paths[] = "$basepath/modules/$modName/properties";
                    // module property (owner)
                    $paths[] = "$basepath/modules/$tplModule/properties";
                }
            }
            // fallback on dd template (dynamicdata)
            if ($modName != 'dynamicdata' && $tplModule != 'dynamicdata')
                $paths[] =  "$basepath/modules/dynamicdata/properties";    
        }
        // look in code 
        if ($modName == 'auto') {
            // standalone property, called in standalone context (owner)
            $paths[] = "{$codePath}properties/$propertyName/xartemplates";
        } else {
            // property called in module context
            if ($tplModule == 'auto') {
                // standalone property (caller) 
                $paths[] = "{$codePath}modules/$modName/xartemplates/properties";
                // standalone property (owner)
                $paths[] = "{$codePath}properties/$propertyName/xartemplates";
            } else {
                // module property (caller)
                $paths[] = "{$codePath}modules/$modName/xartemplates/properties";
                // module property (owner)
                $paths[] = "{$codePath}modules/$tplModule/xartemplates/properties";
            }
        }
        // fallback on dd template (dynamicdata)
        if ($modName != 'dynamicdata' && $tplModule != 'dynamicdata')
            $paths[] =  "{$codePath}modules/dynamicdata/xartemplates/properties";  
        
        $tplFiles = array($tplName, $tplBase);

        $sourceFileName = '';
        foreach ($paths as $path) {
            foreach ($tplFiles as $tplFile) {       
                if (!file_exists("$path/$tplFile.xt")) continue;
                $sourceFileName = "$path/$tplFile.xt";
                break 2;
            }
        }

        if (empty($sourceFileName)) 
            throw new FileNotFoundException("DD Element: [$modName],[$tplBase],[$propertyName]");
        
        xarCoreCache::setCached('Templates.DDElement', $cachename, $sourceFileName);
        
        return self::executeFromFile($sourceFileName, $tplData);
*/
    }

/**
 * Get theme template image replacement for a module's image
 *
 * Example:
 * $my_module_image = xarTpl::getImage('button1.png');
 * $other_module_image = xarTpl::getImage('set1/info.png','modules');
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
 * @param   string $modImage the module image url relative to xarimages/
 * @param   string $modName  the module to check for the image <optional>
 * @return  string image url if it exists or module image url if not, or NULL if neither found
 *
 * @todo    provide examples, improve description, add functionality
 * @todo    provide XML URL override flag
 * @todo    XML encode absolute URIs too?
 * @todo    <chris/> Rewrite the above and document correct practice/examples elsewhere
*/
    public static function getImage($fileName, $scope=NULL, $package=NULL)
    {
        // return absolute URIs and URLs "as is"
        if (empty($fileName) || substr($fileName,0,1) == '/' || preg_match('/^https?\:\/\//',$fileName)) {
            return $fileName;
        }
        
        // handle legacy calls still passing module as second param
        // @todo remove this when all modules are passing correct params 
        if ($scope != 'theme' && $scope != 'module' && $scope != 'property' && $scope != 'block') {
            // assume module scope 
            $package = $scope;
            $scope = 'module';
        }
        
        $paths = array();
        switch ($scope) {
            case 'theme':
                // optional theme images to look in passed as third param
                if (!empty($package)) {
                    $package = xarVarPrepForOS($package);
                    $paths[] = self::getThemeDir($package) . '/images/' . $fileName;
                }                
                // current theme images
                $paths[] = self::getThemeDir() . '/images/' . $fileName;
                // common images 
                $paths[] = self::getThemeDir('common') . '/images/' . $fileName;
                break;
            case 'module':
                if (empty($package))
                    list($package) = xarController::$request->getInfo();
                // @checkme: modules is a depency of templates, redundant check?
                if (method_exists('xarMod', 'getBaseInfo')) {
                    $modBaseInfo = xarMod::getBaseInfo($package);
                    if (!isset($modBaseInfo)) return;
                    $modOsDir = $modBaseInfo['osdirectory'];
                } else {
                    $modOsDir = xarVarPrepForOS($package);
                }
                // handle legacy calls to base module images moved to common/images or themename/images
                // @todo remove this when all modules are passing correct params 
                if ($package == 'base') {
                    // current theme images
                    $paths[] = self::getThemeDir() . '/images/' . $fileName;
                    // common images 
                    $paths[] = self::getThemeDir('common') . '/images/' . $fileName;
                }                
                // current theme module images
                $paths[] = self::getThemeDir() . '/modules/' . $modOsDir . '/images/' . $fileName;
                // common module images
                $paths[] = self::getThemeDir('common') . '/modules/' . $modOsDir . '/images/' . $fileName;
                // module images (legacy)
                $paths[] = sys::code() . 'modules/' . $modOsDir . '/xarimages/' . $fileName;
                // module images
                $paths[] = sys::code() . 'modules/' . $modOsDir . '/xartemplates/images/' . $fileName;
                break;
            case 'property':
                if (empty($package)) return;
                $package = xarVarPrepForOS($package);
                // current theme property images
                $paths[] = self::getThemeDir() . '/properties/' . $package . '/images/' . $fileName;
                // common property images
                $paths[] = self::getThemeDir('common') . '/properties/' . $package . '/images/' . $fileName;
                // property images (legacy)
                $paths[] = sys::code() . 'properties/' . $package . '/xarimages/' . $fileName;
                // property images
                $paths[] = sys::code() . 'properties/' . $package . '/xartemplates/images/' . $fileName;
                break;
            case 'block':
                if (empty($package)) return;
                $package = xarVarPrepForOS($package);
                // current theme block images
                $paths[] = self::getThemeDir() . '/blocks/' . $package . '/images/' . $fileName;
                // common block images
                $paths[] = self::getThemeDir('common') . '/blocks/' . $package . '/images/' . $fileName;
                // code/blocks/block/xartemplates/style
                $paths[] = sys::code() . 'blocks/' . $package . '/xarimages/' . $fileName;
                break;
        }
        if (empty($paths)) return;
        
        $filePath = null;
        foreach ($paths as $path) {
            if (!file_exists($path)) continue;
            $filePath = $path;
            break;
        }

        // Turn relative path into an absolute URL
        $webDir = sys::web();
        if (!empty($webDir) && strpos($filePath, $webDir) === 0) {
            $filePath = substr($filePath, strlen($webDir));
        }
        $filePath = xarServer::getBaseURL() . $filePath;

        // Return as an XML URL if required.
        // This will generally have little effect, but is here for
        // completeness to support alternative types of URL.
        if (isset($filePath) && self::$generateXMLURLs) {
            $filePath = htmlspecialchars($filePath);
        }
        return $filePath;
    }

/**
 * Execute a pre-compiled template string with the supplied template variables
 *
 * @access public
 * @param  string $templateCode pre-compiled template code (see xarTpl::compileString)
 * @param  array  $tplData      template variables
 * @return string filled-in template
 * @todo   this is not MLS-aware (never was)
 * @todo   how 'special' should the 'memory' file be, namewise?
 */
    public static function string($templateCode, &$tplData)
    {
        // Pretend as if the cache is fully operational and we'll be fine
        xarTemplateCache::saveEntry('memory',$templateCode);

        // Execute the cache file
        sys::import('blocklayout.template.compiled');
        $compiled = new CompiledTemplate(xarTemplateCache::cacheFile('memory'));
        try {
            $caching = xarConfigVars::get(null, 'Site.BL.MemCacheTemplates');
        } catch (Exception $e) {
            $caching = 0;
        }
        $out = $compiled->execute($tplData, $caching);
        return $out;
    }

/**
 * Execute a specific template file with the supplied template variables
 *
 * @access public
 * @param  string $fileName location of the template file
 * @param  array  $tplData  template variables
 * @return string filled-in template
 */
    public static function file($fileName, &$tplData)
    {
        return self::executeFromFile($fileName, $tplData);
    }

/**
 * Compile a template string for storage and/or later use in xarTpl::string()
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
        sys::import('xaraya.templating.compiler');
        $compiler = XarayaCompiler::instance();
        return $compiler->compileString($templateSource);
    }

/**
 * Renders a page template.
 *
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * 
 * @param  string $mainModuleOutput       the module output
 * @param  string $pageTemplate           the page template to use (without extension .xt)
 * @return string
 *
 * @todo Needs a rewrite, i.e. finalisation of tplOrder scenario 
 */
    public static function renderPage($mainModuleOutput, $pageTemplate = NULL)
    {
        if (empty($pageTemplate)) $pageTemplate = self::getPageTemplateName();

        // get page template source (current > common)
        $sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $pageTemplate, null, 'pages');

        $tpl = (object) null; // Create an object to hold the 'specials'
        $tpl->pageTitle = self::getPageTitle();

        $tplData = array(
            'tpl'                      => $tpl,
            '_bl_mainModuleOutput'     => $mainModuleOutput,
        );

        return self::executeFromFile($sourceFileName, $tplData);
    }

/**
 * Render a block box
 *
 * @access public
 * @param  array  $blockInfo  Information on the block
 * @param  string $templateName string
 * @return boolean xarTpl::executeFromFile($sourceFileName, $blockInfo)
 *
 * @todo the search logic for the templates can perhaps use the private function?
 * @todo implement common templates in cascade 
 */
    public static function renderBlockBox($blockInfo, $templateName = NULL)
    {
        // look for specific templateName.xt (current > common)
        if (!empty($templateName))
            $sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $templateName, null, 'blocks');
        // no specific template, fallback to default.xt (current > common)
        if (empty($sourceFileName))
            $sourceFileName = self::getScopeFileName('theme', self::getThemeName(), 'default', null, 'blocks');
        // no default, fallback to blocks module block.xt (current > common > module)
        if (empty($sourceFileName))
            $sourceFileName = self::getScopeFileName('module', 'blocks', 'block', null, 'blocks');
        // sanity check: shouldn't happen since block.xt is a core module template, but just in case
        if (empty($sourceFileName))
            throw new FileNotFoundException(null, 'Could not find block outer template block.xt');
    
        return self::executeFromFile($sourceFileName, $blockInfo);        
    }

/**
 * xar:template tag handler
 * Include a subtemplate from wherever
 *
 * @access public
 * @param  string $tplType      scope in which to look for templates [theme|module|block|property]
 * @param  string $package      name of the theme|module|block|property supplying the template
 * @param  string $tplName      The name of the template to use
 * @param  array  $tplData      array of data for the template
 * @param  string $tplPart      Optional sub path to look for templates in relative to template path
 * @param  string $callerMod    Optional name of the module calling the template, if different from $package
 * @throws FileNotFoundException
 * @return string self::executeFromFile($sourceFileName, $tplData);
**/
    public static function includeTemplate($tplType, $package, $tplBase, $tplData=array(), $tplPart='includes', $tplName=null, $callerMod=null)
    {
        // chris: added this to replicate behaviour of includeModuleTemplate()
        $packages = array_map('trim', explode(',', $package));
        // @checkme: do we really want to fall back on dd in all cases here? 
        if ($tplType == 'module' && !in_array('dynamicdata', $packages))
            $packages[] = 'dynamicdata';
        foreach ($packages as $tplPkg) {
            if (!$sourceFileName = self::getScopeFileName($tplType, $tplPkg, $tplBase, $tplName, $tplPart, $callerMod))
                continue;
            break;
        }
        if (empty($sourceFileName)) {
            // Not found: raise an exception        
            $vars = array($tplType, $tplPart, $tplBase, $package);
            $msg = 'Missing #(1) include template #(2)/#(3) in #(4)';
            throw new FileNotFoundException($vars, $msg);
        }
        return self::executeFromFile($sourceFileName, $tplData);            
    }

/**
 * Include a subtemplate from the theme space
 * @NOTE: this method is no longer used and is targetted for removal
 *
 * @access public
 * @param  string $templateName Basically handler function for <xar:template type="theme".../>
 * @param  array  $tplData      template variables
 * @return string
 */
    public static function includeThemeTemplate($templateName, $tplData)
    {
        // get source file (current > common)
        $sourceFileName = self::getScopeFileName('theme', self::getThemeName(), $templateName, null, 'includes');
        if (empty($sourceFileName))
            // Not found: raise an exception
            throw new FileNotFoundException($templateName, 'Could not find include template #(1).xt');
        return self::executeFromFile($sourceFileName, $tplData);
    }

/**
 * Include a subtemplate from the module space
 * @NOTE: this method is no longer used and is targetted for removal
 *
 * @access public
 * @param  string $modName      name of the module from which to include the template
 * @param  string $templateName Basically handler function for <xar:template type="module".../>
 * @param  array  $tplData      template variables
 * @param  array  $propertyName name of the property from which to include the template
 * @throws FileNotFoundException
 * @return string
 */
    public static function includeModuleTemplate($modName, $templateName, $tplData, $propertyName='')
    {
        // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
        $templateName = xarVarPrepForOS($templateName);
        $themeDir = self::getThemeDir();
        $commonDir = self::getThemeDir('common');
        
        // @checkme: when do we pass a list of modules to this function?        
        $modules = explode(',',$modName);
        foreach ($modules as $module) {
            $thismodule = trim($module);
            // module include in current theme
            $sourceFileName = "$themeDir/modules/$thismodule/includes/$templateName.xt";
            if (file_exists($sourceFileName)) break;
            // module include in common
            $sourceFileName = "$commonDir/modules/$thismodule/includes/$templateName.xt";
            if (file_exists($sourceFileName)) break;            
            // module include in module
            $sourceFileName = sys::code() . "modules/$thismodule/xartemplates/includes/$templateName.xt";
            if (file_exists($sourceFileName)) break;
            if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true) {
                $sourceFileName = sys::code() . "modules/$thismodule/xartemplates/includes/$templateName.xd";
                if (file_exists($sourceFileName)) break;
            }
            // @checkme: dd as fall back in all cases? what if dd happens to supply 
            // a same named but unrelated template?
            if (!file_exists($sourceFileName)) {
                $sourceFileName = sys::code() . "modules/dynamicdata/xartemplates/includes/$templateName.xt";
            }
        }
        if (file_exists($sourceFileName)) return self::executeFromFile($sourceFileName, $tplData);

        // Check for a property template as a fallback
        // @checkme: again, what happens if we got a match from a same named but unrelated template?
        // property include in current theme
        $sourceFileName = "$themeDir/properties/$propertyName/templates/includes/$templateName.xt";
        if (file_exists($sourceFileName)) return self::executeFromFile($sourceFileName, $tplData);
        // property include in common
        $sourceFileName = "$commonDir/properties/$propertyName/xartemplates/includes/$templateName.xt";
        if (file_exists($sourceFileName)) return self::executeFromFile($sourceFileName, $tplData); 
        // property include in property       
        $sourceFileName = sys::code() . "properties/$propertyName/xartemplates/includes/$templateName.xt";
        if (file_exists($sourceFileName)) return self::executeFromFile($sourceFileName, $tplData);

        // Not found: raise an exception
        throw new FileNotFoundException($templateName, 'Could not find include template #(1).xt');
    }

/* PRIVATE FUNCTIONS */

/**
 * Execute template from file
 *
 * @access private
 * @param  string $sourceFileName       From which file do we want to execute? Assume it exists by now ;-)
 * @param  array  $tplData              Template variables
 * @param  string $tplType              'module' or 'page'
 * @return string generated output from the file
 * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
 * @todo  make the checking whether templatecode is set more robust (related to templated exception handling)
 */
    private static function executeFromFile($sourceFileName, $tplData, $tplType = 'module')
    {
        assert('!empty($sourceFileName); /* The source file for the template is empty in xarTpl::executeFromFile */');
        assert('is_array($tplData); /* Template data should always be passed in as array */');

        // cache frequently-used cachedfilenames
        if (xarCoreCache::isCached('Templates.ExecuteFromFile', $sourceFileName)) {
            $cachedFileName = xarCoreCache::getCached('Templates.ExecuteFromFile', $sourceFileName);

        } else {
            // Load translations for the template
            xarMLSLoadTranslations($sourceFileName);

            xarLogMessage("Using template : $sourceFileName");
            $templateCode = null;

            // Determine if we need to compile this template
            if (xarTemplateCache::isDirty($sourceFileName)) {
                // Get an instance of SourceTemplate
                sys::import('xaraya.templating.source');
                $srcTemplate = new XarayaSourceTemplate($sourceFileName);

                // Compile it
                // @todo return a CompiledTemplate object here?
                $templateCode = $srcTemplate->compile();

                // Save the entry in templatecache (if active)
                xarTemplateCache::saveEntry($sourceFileName,$templateCode);
            }

            // Execute either the compiled template, or the code determined
            // @todo get rid of the cachedFileName usage
            $cachedFileName = xarTemplateCache::cacheFile($sourceFileName);

            xarCoreCache::setCached('Templates.ExecuteFromFile', $sourceFileName, $cachedFileName);
        }

        // Execute the compiled template from the cache file
        // @todo the tplType should be irrelevant
        sys::import('blocklayout.template.compiled');
        $compiled = new CompiledTemplate($cachedFileName,$sourceFileName,$tplType);
        try {
            $caching = xarConfigVars::get(null, 'Site.BL.MemCacheTemplates');
        } catch (Exception $e) {
            $caching = 0;
        }
        $output = $compiled->execute($tplData, $caching);
        return $output;
    }



/* END PRIVATE FUNCTIONS */


/**
 * Output template
 *
 * @access public
 * @param  string $sourceFileName
 * @param  string $tplOutput
 * @return void
 *
 * @todo Rethink this function, it contains hardcoded xhtml
 */
    public static function outputTemplate($sourceFileName, &$tplOutput)
    {
        // flag used to determine if the header content has been found.
        static $isHeaderContent;
        if(!isset($isHeaderContent))
            $isHeaderContent = false;

        $finalTemplate ='';
        try {
            if(self::outputTemplateFilenames() && (in_array(xarUserGetVar('uname'),xarConfigVars::get(null, 'Site.User.DebugAdmins')))) {
                $outputStartComment = true;
                if($isHeaderContent === false) {
                    if($isHeaderContent = self::modifyHeaderContent($sourceFileName, $tplOutput))
                        $outputStartComment = false;
                }
                // optionally show template filenames if start comment has not already
                // been added as part of a header determination.
                if($outputStartComment === true)
                    $finalTemplate .= "<!-- start: " . $sourceFileName . " -->\n";
                $finalTemplate .= $tplOutput;
                $finalTemplate .= "<!-- end: " . $sourceFileName . " -->\n";
            } else {
                $finalTemplate .= $tplOutput;
            }
        } catch (Exception $e) {
            $finalTemplate .= $tplOutput;
        }
        return $finalTemplate;
    }

/**
 * Output php comment block in templates
 *
 * @access public
 * @return int value of xarTpl::showPHPCommentBlockInTemplates (0 or 1)
 */
    public static function outputPHPCommentBlockInTemplates()
    {
        try {
            // We need to make sure enough of the core is loaded to run this
            $allowed = function_exists('xarUserGetVar');
            if ($allowed && 
                !isset(self::$showPHPCommentBlockInTemplates) && 
                (in_array(xarUserGetVar('uname'),xarConfigVars::get(null, 'Site.User.DebugAdmins')))) {
                // Default to not show the comments
                self::$showPHPCommentBlockInTemplates = 0;
                // @checkme: modules is a depency of templates, redundant check?
                if (method_exists('xarModVars','Get')){
                    $showphpcbit = xarModVars::get('themes', 'ShowPHPCommentBlockInTemplates');
                    if (!empty($showphpcbit)) {
                        self::$showPHPCommentBlockInTemplates = 1;
                    }
                } else {
                    self::$showPHPCommentBlockInTemplates = 0;
                }
            }                    
        } catch (Exception $e) {
            self::$showPHPCommentBlockInTemplates = 0;
        }
        return self::$showPHPCommentBlockInTemplates;
    }

/**
 * Output template filenames
 *
 * @access public
 * @return int value of xarTpl::showTemplateFilenames (0 or 1)
 *
 * @todo Check whether the check for xarModVars::get is needed
 * @todo Rethink this function
 */
    public static function outputTemplateFilenames()
    {
        if (!isset(self::$showTemplateFilenames)) {
            // Default to not showing it
            self::$showTemplateFilenames = 0;
            // @checkme: modules is a depency of templates, redundant check?
            if (method_exists('xarModVars','Get')){
                $showtemplates = xarModVars::get('themes', 'ShowTemplates');
                if (!empty($showtemplates)) {
                    self::$showTemplateFilenames = 1;
                }
            }
        }
        return self::$showTemplateFilenames;
    }

/**
 * Modify header content
 *
 * Attempt to determine if $tplOutput contains header content and if
 * so append a start comment after the first matched header tag
 * found.
 *
 * @access public
 * @param  string $sourceFileName
 * @param  string $tplOutput
 * @return boolean found header content
 *
 * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
 *       greedy in more complex xml documents and others.
 * @todo The doctype of the output belongs in a template somewhere (probably the xar:blocklayout tag, as an attribute
 */
    public static function modifyHeaderContent($sourceFileName, &$tplOutput)
    {
        $foundHeaderContent = false;

        // $headerTagsRegexes is an array of string regexes to match tags that could
        // be sent as part of a header. Important: the order here should be inside out
        // as the first regex that matches will have a start comment appended.
        // fixes bugs: #1427, #1190, #603
        // - Comments that precede <!doctype... cause ie6 not to sniff the doctype
        //   correctly.
        // - xml parsers dont like comments that precede xml output.
        // At this time attempting to match <!doctype... and <?xml version... tags.
        // This is about the best we can do now, until we process xar documents with an xml parser and actually 'parse'
        // the document.
        $headerTagRegexes = array('<!DOCTYPE[^>].*]>',// eg. <!DOCTYPE doc [<!ATTLIST e9 attr CDATA "default">]>
                                  '<!DOCTYPE[^>]*>',// eg. <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                                  '<\?xml\s+version[^>]*\?>');// eg. <?xml version="1.0"? > // remove space between qmark and gt

        foreach($headerTagRegexes as $headerTagRegex) {
            if(preg_match("/$headerTagRegex/smix", $tplOutput, $matchedHeaderTag)) {
                // FIXME: the next line assumes that we are not in a comment already, no way of knowing that,
                // keep the functionality for now, but dont change more than necessary (see bug #3559)
                // $startComment = '<!-- start(output actually commenced before header(s)): ' . $sourceFileName . ' -->';
                $startComment ='';
                // replace matched tag with an appended start comment tag in the first match
                // in the template output $tplOutput
                $tplOutput = preg_replace("/$headerTagRegex/smix", $matchedHeaderTag[0] . $startComment, $tplOutput, 1);
                // dont want start comment to be sent below as it has already been added.
                $foundHeaderContent = true;
                break;
            }
        }
        return $foundHeaderContent;
    }

}

/* REPLACED FUNCTIONS */

function xarTpl_init(&$args)
{
    return xarTpl::init($args);
}

function xarTplGetThemeName()
{
    return xarTpl::getThemeName();
}

function xarTplSetThemeName($themeName)
{
    return xarTpl::setThemeName($themeName);
}

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
function xarTplGetThemeDir($theme=null)
{
    return xarTpl::getThemeDir($theme);
}

function xarTplGetPageTemplateName()
{
    return xarTpl::getPageTemplateName();
}

function xarTplSetPageTemplateName($templateName)
{
    return xarTpl::setPageTemplateName($templateName);
}

function xarTplGetDoctype()
{
    return xarTpl::getDocType();
}

function xarTplSetDoctype($doctypeName)
{
    return xarTpl::setDoctype($doctypeName);
}

function xarTplSetPageTitle($title = NULL, $module = NULL)
{
    return xarTpl::setPageTitle($title,$module);
}

function xarTplGetPageTitle()
{
    return xarTpl::getPageTitle();
}

function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    return xarTpl::module($modName,$modType,$funcName,$tplData,$templateName);
}

function xarTplBlock($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL)
{
    return xarTpl::block($modName, $blockType, $tplData, $tplName, $tplBase);
}

function xarTplProperty($modName, $propertyName, $tplType = 'showoutput', $tplData = array(), $tplBase = NULL)
{
    return xarTpl::property($modName,$propertyName,$tplType,$tplData,$tplBase);
}

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
function xarTplGetImage($modImage, $modName = NULL)
{    
    return xarTpl::getImage($modImage,$modName);
}

function xarTplString($templateCode, &$tplData)
{
    return xarTpl::string($templateCode,$tplData);
}

function xarTplFile($fileName, &$tplData)
{
    return xarTpl::file($fileName,$tplData);
}

function xarTplCompileString($templateSource)
{
    return xarTpl::compileString($templateSource);
}

function xarTpl_renderPage($mainModuleOutput, $pageTemplate = NULL)
{
    return xarTpl::renderPage($mainModuleOutput,$pageTemplate);
}

function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    return xarTpl::renderBlockBox($blockInfo,$templateName);
}

function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    return xarTpl::includeThemeTemplate($templateName,$tplData);
}

function xarTpl_includeModuleTemplate($modName, $templateName, $tplData, $propertyName='')
{
    return xarTpl::includeModuleTemplate($modName,$templateName,$tplData,$propertyName);
}

// PRIVATE FUNCTIONS

// FIXME: this cannot be private since it's used by the mail module
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

function xarTpl_outputTemplate($sourceFileName, &$tplOutput)
{
    return xarTpl::outputTemplate($sourceFileName,$tplOutput);
}

function xarTpl_outputPHPCommentBlockInTemplates()
{
    return xarTpl::outputPHPCommentBlockInTemplates();
}

function xarTpl_outputTemplateFilenames()
{
    return xarTpl::outputTemplateFilenames();
}

function xarTpl_modifyHeaderContent($sourceFileName, &$tplOutput)
{
    return xarTpl::modifyHeaderContent($sourceFileName, $tplOutput);
}

?>