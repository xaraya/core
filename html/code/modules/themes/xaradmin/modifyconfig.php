<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
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
    if (!xarSecurity::check('AdminThemes')) return;

    $data = [];
    if (!xarVar::fetch('phase',        'str:1:100', $phase,       'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) return;
    if (!xarVar::fetch('sitename', 'str', $data['sitename'], xarModVars::get('themes', 'SiteName'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('separator', 'str:1:', $data['separator'], xarModVars::get('themes', 'SiteTitleSeparator'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('pagetitle', 'str:1:', $data['pagetitle'], 'default', xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('showphpcbit', 'checkbox', $data['showphpcbit'], (bool)xarModVars::get('themes', 'ShowPHPCommentBlockInTemplates'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('showtemplates', 'checkbox', $data['showtemplates'], (bool)xarModVars::get('themes', 'ShowTemplates'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('cachetemplates', 'checkbox', $data['cachetemplates'], xarConfigVars::get(null, 'Site.BL.CacheTemplates'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('memcachetemplates', 'checkbox', $data['memcachetemplates'], xarConfigVars::get(null, 'Site.BL.MemCacheTemplates'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('variable_dump', 'checkbox', $data['variable_dump'], (bool)xarModVars::get('themes', 'variable_dump'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('slogan', 'str', $data['slogan'], xarModVars::get('themes', 'SiteSlogan'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('footer', 'str', $data['footer'], xarModVars::get('themes', 'SiteFooter'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('copyright', 'str', $data['copyright'], xarModVars::get('themes', 'SiteCopyRight'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('AtomTag', 'str:1:', $data['atomtag'], (bool)xarModVars::get('themes', 'AtomTag'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('compresswhitespace', 'int', $data['compresswhitespace'], 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('doctype', 'str:1', $data['doctype'], 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('debugmode', 'int', $data['debugmode'], 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('exceptionsdisplay', 'int', $data['exceptionsdisplay'], 0, xarVar::NOT_REQUIRED)) return;

    if (!xarVar::fetch('themedir','str:1:',$data['defaultThemeDir'],'themes',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('adminpagemenu', 'checkbox', $data['adminpagemenu'], (bool)xarModVars::get('themes', 'adminpagemenu'), xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('userpagemenu', 'checkbox', $data['userpagemenu'], (bool)xarModVars::get('themes', 'userpagemenu'), xarVar::NOT_REQUIRED)) {return;}
//    if (!xarVar::fetch('usedashboard', 'checkbox', $data['usedashboard'], (bool)xarModVars::get('themes', 'usedashboard'), xarVar::NOT_REQUIRED)) {return;}
//    if (!xarVar::fetch('dashtemplate', 'str:1:', $data['dashtemplate'], trim(xarModVars::get('themes', 'dashtemplate')), xarVar::NOT_REQUIRED)) {return;}

    if (!xarVar::fetch('selsort','str:1:',$data['selsort'],'plain',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('selfilter','int',$data['selfilter'],xarMod::STATE_ANY,xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('hidecore', 'checkbox', $data['hidecore'], false, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('selstyle','str:1:',$data['selstyle'],'plain',xarVar::NOT_REQUIRED)) return;
    
    // experimental combine/compress css options
    if (!xarVar::fetch('combinecss', 'checkbox', $data['combinecss'], false, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('compresscss', 'checkbox', $data['compresscss'], false, xarVar::NOT_REQUIRED)) return;
    // can't compress if not combined :)    
    if ($data['combinecss'] == false) $data['compresscss'] = false;
    if (!xarVar::fetch('allowajax', 'checkbox', $data['allowajax'], false, xarVar::NOT_REQUIRED)) return;
    
    if (!xarVar::fetch('enable_user_menu', 'checkbox',
        $data['enable_user_menu'], xarModVars::get('themes', 'enable_user_menu'), xarVar::NOT_REQUIRED)) return;
    

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
            if (!xarSec::confirmAuthKey()) {
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
            xarModVars::set('themes', 'userpagemenu', $data['userpagemenu']);
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
            xarConfigVars::set(null, 'Site.BL.DocType',$data['doctype']);
            xarConfigVars::set(null, 'Site.BL.ExceptionDisplay',$data['exceptionsdisplay']);
            xarConfigVars::set(null, 'Site.Core.AllowAJAX',$data['allowajax']);
            xarModVars::set('themes', 'hidecore', $data['hidecore']);
            xarModVars::set('themes', 'selstyle', $data['selstyle']);
            xarModVars::set('themes', 'selfilter', $data['selfilter']);
            xarModVars::set('themes', 'selsort', $data['selsort']);

            // css combine/compress options
            xarModVars::set('themes', 'css.combined', $data['combinecss']);
            xarModVars::set('themes', 'css.compressed', $data['compresscss']);

            xarModVars::set('themes', 'debugmode', $data['debugmode']);

            sys::import('modules.dynamicdata.class.properties.master');
            $caches = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
            $caches->checkInput('flushcaches');
            xarModVars::set('themes','flushcaches', $caches->value);
            
            // Flush the caches
            $cachestoflush = $caches->getValue();
            /** @var FilePickerProperty $picker */
            $picker = DataPropertyMaster::getProperty(array('name' => 'filepicker'));
            foreach ($cachestoflush as $cachetoflush) {
                $picker->initialization_basedirectory = sys::varpath() . "/cache/" . $cachetoflush;
                if (!file_exists($picker->initialization_basedirectory)) continue;
                $files = $picker->getOptions();
                foreach ($files as $file) unlink($picker->initialization_basedirectory . "/" . $file['id']);
            }
            
            xarController::redirect(xarController::URL('themes', 'admin', 'modifyconfig'));
            return true;

        case 'flush':
            // Flush the cache directories
            sys::import('modules.dynamicdata.class.properties.master');
            $caches = DataPropertyMaster::getProperty(array('name' => 'checkboxlist'));
            $caches->checkInput('flushcaches');
            xarModVars::set('themes','flushcaches', $caches->value);
            // Flush the caches
            $cachestoflush = $caches->getValue();
            /** @var FilePickerProperty $picker */
            $picker = DataPropertyMaster::getProperty(array('name' => 'filepicker'));
            foreach ($cachestoflush as $cachetoflush) {
                $picker->initialization_basedirectory = sys::varpath() . "/cache/" . $cachetoflush;
                if (!file_exists($picker->initialization_basedirectory)) continue;

                $dir = new RelativeDirectoryIterator($picker->initialization_basedirectory);

                for($dir->rewind();$dir->valid();$dir->next()) {
                    if($dir->isDir()) continue; // no dirs
                    if($dir->isDot()) continue; // skip . and ..
                    $name = $dir->getFileName();
                    if(strpos($name, '.') !== (int) 0)
                    unlink($picker->initialization_basedirectory . "/" . $name);
                }
            }
            
            // Empty the cache_data table in the database
            $caches->checkInput('flushdbcaches');
            xarModVars::set('themes','flushdbcaches', $caches->value);
            $cachestoflush = $caches->getValue();
            sys::import('xaraya.structures.query');
            foreach ($cachestoflush as $cachetoflush) {
                if ($cachetoflush == 'dynamicdata') {
                    $q = new Query('DELETE', xarDB::getPrefix() . '_cache_data');
                    $q->run();
                }
            }
            break;
    }
    return $data;
}
