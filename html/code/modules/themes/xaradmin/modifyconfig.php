<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @return mixed data array for the template display or output display string if invalid data submitted
 *
 * @author Marty Vance
 */
function themes_admin_modifyconfig()
{
    // Security
    if (!xarSecurityCheck('AdminThemes')) return;

    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('sitename', 'str', $data['sitename'], xarModVars::get('themes', 'SiteName'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('separator', 'str:1:', $data['separator'], xarModVars::get('themes', 'SiteTitleSeparator'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pagetitle', 'str:1:', $data['pagetitle'], 'default', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showphpcbit', 'checkbox', $data['showphpcbit'], (bool)xarModVars::get('themes', 'ShowPHPCommentBlockInTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showtemplates', 'checkbox', $data['showtemplates'], (bool)xarModVars::get('themes', 'ShowTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cachetemplates', 'checkbox', $data['cachetemplates'], xarConfigVars::get(null, 'Site.BL.CacheTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('memcachetemplates', 'checkbox', $data['memcachetemplates'], xarConfigVars::get(null, 'Site.BL.MemCacheTemplates'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('variable_dump', 'checkbox', $data['variable_dump'], (bool)xarModVars::get('themes', 'variable_dump'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('slogan', 'str', $data['slogan'], xarModVars::get('themes', 'SiteSlogan'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('footer', 'str', $data['footer'], xarModVars::get('themes', 'SiteFooter'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyright', 'str', $data['copyright'], xarModVars::get('themes', 'SiteCopyRight'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('AtomTag', 'str:1:', $data['atomtag'], (bool)xarModVars::get('themes', 'AtomTag'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('compresswhitespace', 'int', $data['compresswhitespace'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('debugmode', 'int', $data['debugmode'], 0, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('themedir','str:1:',$data['defaultThemeDir'],'themes',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('adminpagemenu', 'checkbox', $data['adminpagemenu'], (bool)xarModVars::get('themes', 'adminpagemenu'), XARVAR_NOT_REQUIRED)) {return;}
//    if (!xarVarFetch('usedashboard', 'checkbox', $data['usedashboard'], (bool)xarModVars::get('themes', 'usedashboard'), XARVAR_NOT_REQUIRED)) {return;}
//    if (!xarVarFetch('dashtemplate', 'str:1:', $data['dashtemplate'], trim(xarModVars::get('themes', 'dashtemplate')), XARVAR_NOT_REQUIRED)) {return;}

    if (!xarVarFetch('selsort','str:1:',$data['selsort'],'plain',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selfilter','int',$data['selfilter'],XARMOD_STATE_ANY,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('hidecore', 'checkbox', $data['hidecore'], false, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('selstyle','str:1:',$data['selstyle'],'plain',XARVAR_NOT_REQUIRED)) return;
    
    // experimental combine/compress css options
    if (!xarVarFetch('combinecss', 'checkbox', $data['combinecss'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('compresscss', 'checkbox', $data['compresscss'], false, XARVAR_NOT_REQUIRED)) return;
    // can't compress if not combined :)    
    if ($data['combinecss'] == false) $data['compresscss'] = false;
    
    if (!xarVarFetch('enable_user_menu', 'checkbox',
        $data['enable_user_menu'], xarModVars::get('themes', 'enable_user_menu'), XARVAR_NOT_REQUIRED)) return;
    

    // Dashboard
//    if (!isset($data['dashtemplate']) || trim($data['dashtemplate']=='')) {
//        $data['dashtemplate']='dashboard';
//    }

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'themes'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
    $data['module_settings']->getItem();

    sys::import('modules.dynamicdata.class.properties.master');
    $data['user_themes'] = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
    $data['user_themes']->options = xarMod::apiFunc('themes', 'admin', 'dropdownlist', array('Class' => 2));
    $data['user_themes']->setValue(xarModVars::get('themes', 'user_themes'));
    $data['user_themes']->layout = 'vertical';
    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            $isvalid = $data['module_settings']->checkInput();
            $andvalid = ($data['enable_user_menu'] != false) ? $data['user_themes']->checkInput('user_themes') : true;
          
            if (!$isvalid || !$andvalid) {
                return xarTpl::module('themes','admin','modifyconfig', $data);        
            } else {
                $itemid = $data['module_settings']->updateItem();
                xarModVars::set('themes', 'enable_user_menu', $data['enable_user_menu']);
                if (isset($data['user_themes']->value))
                    xarModVars::set('themes','user_themes', $data['user_themes']->value);
            }
            xarModVars::set('themes', 'SiteName', $data['sitename']);
            xarModVars::set('themes', 'SiteTitleSeparator', $data['separator']);
            xarModVars::set('themes', 'SiteTitleOrder', $data['pagetitle']);
            xarModVars::set('themes', 'SiteSlogan', $data['slogan']);
            xarModVars::set('themes', 'SiteCopyRight', $data['copyright']);
            xarModVars::set('themes', 'SiteFooter', $data['footer']);
            xarModVars::set('themes', 'ShowPHPCommentBlockInTemplates', $data['showphpcbit']);
            xarModVars::set('themes', 'ShowTemplates', $data['showtemplates']);
            xarModVars::set('themes', 'AtomTag', $data['atomtag']);
            xarModVars::set('themes', 'variable_dump', $data['variable_dump']);
            xarModVars::set('themes', 'adminpagemenu', $data['adminpagemenu']);
//            xarModVars::set('themes', 'usedashboard', $data['usedashboard']);
//            xarModVars::set('themes', 'dashtemplate', $data['dashtemplate']);
            // <chris/> Instead of setting the base theme config var dir directly, 
            // let xarTpl take care of it, it'll complain if the directory doesn't
            // exist or the current theme isn't in the directory specified  
            // xarConfigVars::set(null,'Site.BL.ThemesDirectory', $data['defaultThemeDir']);
            xarTpl::setBaseDir($data['defaultThemeDir']);
            xarConfigVars::set(null, 'Site.BL.CacheTemplates',$data['cachetemplates']);
            xarConfigVars::set(null, 'Site.BL.MemCacheTemplates',$data['memcachetemplates']);
            xarConfigVars::set(null, 'Site.BL.CompressWhitespace',$data['compresswhitespace']);
            xarModVars::set('themes', 'hidecore', $data['hidecore']);
            xarModVars::set('themes', 'selstyle', $data['selstyle']);
            xarModVars::set('themes', 'selfilter', $data['selfilter']);
            xarModVars::set('themes', 'selsort', $data['selsort']);

            // css combine/compress options
            xarModVars::set('themes', 'css.combined', $data['combinecss']);
            xarModVars::set('themes', 'css.compressed', $data['compresscss']);

            xarModVars::set('themes', 'debugmode', $data['debugmode']);

           
            // Adjust the usermenu hook according to the setting
            /* The usermenu isn't a hook...
            sys::import('xaraya.structures.hooks.observer');
            $observer = new BasicObserver('themes','user','usermenu');
            $subject = new HookSubject('roles');
            if (xarModVars::get('themes','enable_user_menu')) {
                $subject->attach($observer);
            } else {
                $subject->detach($observer);
            }
            */
            
            sys::import('modules.dynamicdata.class.properties.master');
            $caches = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
            $caches->checkInput('bl_flushcaches');
            xarModVars::set('themes','flushcaches', $caches->value);
            
            // Flush the caches
            $cachestoflush = $caches->getValue();
            $picker = DataPropertyMaster::getProperty(array('name' => 'filepicker'));
            foreach ($cachestoflush as $cachetoflush) {
                $picker->initialization_basedirectory = sys::varpath() . "/cache/" . $cachetoflush;
                if (!file_exists($picker->initialization_basedirectory)) continue;
                $files = $picker->getOptions();
                foreach ($files as $file) unlink($picker->initialization_basedirectory . "/" . $file['id']);
            }
            
            break;
        case 'flush':
            sys::import('modules.dynamicdata.class.properties.master');
            $caches = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
            $caches->checkInput('flushcaches');
            xarModVars::set('themes','flushcaches', $caches->value);
            
            // Flush the caches
            $cachestoflush = $caches->getValue();
            $picker = DataPropertyMaster::getProperty(array('name' => 'filepicker'));
            foreach ($cachestoflush as $cachetoflush) {
                $picker->initialization_basedirectory = sys::varpath() . "/cache/" . $cachetoflush;
                if (!file_exists($picker->initialization_basedirectory)) continue;
                $files = $picker->getOptions();
                foreach ($files as $file) unlink($picker->initialization_basedirectory . "/" . $file['id']);
            }
            break;
    }
    return $data;
}
?>
