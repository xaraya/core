<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use Xaraya\Modules\Themes\AdminApi;
use DataPropertyMaster;
use Exception;
use FilePickerProperty;
use Query;
use RelativeDirectoryIterator;
use xarConfigVars;
use xarController;
use xarDB;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @author Marty Vance
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminThemes')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('sitename', $data['sitename'], 'str', xarModVars::get('themes', 'SiteName'));
        $this->var()->find('separator', $data['separator'], 'str:1:', xarModVars::get('themes', 'SiteTitleSeparator'));
        $this->var()->find('pagetitle', $data['pagetitle'], 'str:1:', 'default');
        $this->var()->find('showphpcbit', $data['showphpcbit'], 'checkbox', (bool) xarModVars::get('themes', 'ShowPHPCommentBlockInTemplates'));
        $this->var()->find('showtemplates', $data['showtemplates'], 'checkbox', (bool) xarModVars::get('themes', 'ShowTemplates'));
        $this->var()->find('cachetemplates', $data['cachetemplates'], 'checkbox', xarConfigVars::get(null, 'Site.BL.CacheTemplates'));
        $this->var()->find('memcachetemplates', $data['memcachetemplates'], 'checkbox', xarConfigVars::get(null, 'Site.BL.MemCacheTemplates'));
        $this->var()->find('variable_dump', $data['variable_dump'], 'checkbox', (bool) xarModVars::get('themes', 'variable_dump'));
        $this->var()->find('slogan', $data['slogan'], 'str', xarModVars::get('themes', 'SiteSlogan'));
        $this->var()->find('footer', $data['footer'], 'str', xarModVars::get('themes', 'SiteFooter'));
        $this->var()->find('copyright', $data['copyright'], 'str', xarModVars::get('themes', 'SiteCopyRight'));
        $this->var()->find('AtomTag', $data['atomtag'], 'str:1:', (bool) xarModVars::get('themes', 'AtomTag'));
        $this->var()->find('compresswhitespace', $data['compresswhitespace'], 'int', 0);
        $this->var()->find('doctype', $data['doctype'], 'str:1', 0);
        $this->var()->find('debugmode', $data['debugmode'], 'int', 0);
        $this->var()->find('exceptionsdisplay', $data['exceptionsdisplay'], 'int', 0);

        $this->var()->find('themedir', $data['defaultThemeDir'], 'str:1:', 'themes');
        $this->var()->find('adminpagemenu', $data['adminpagemenu'], 'checkbox', (bool) xarModVars::get('themes', 'adminpagemenu'));
        $this->var()->find('userpagemenu', $data['userpagemenu'], 'checkbox', (bool) xarModVars::get('themes', 'userpagemenu'));
        //    $this->var()->find('usedashboard', $data['usedashboard'], 'checkbox', (bool)xarModVars::get('themes', 'usedashboard'));
        //    $this->var()->find('dashtemplate', $data['dashtemplate'], 'str:1:', trim(xarModVars::get('themes', 'dashtemplate')));

        $this->var()->find('selsort', $data['selsort'], 'str:1:', 'plain');
        $this->var()->find('selfilter', $data['selfilter'], 'int', xarMod::STATE_ANY);
        $this->var()->check('hidecore', $data['hidecore'], 'checkbox', false);
        $this->var()->find('selstyle', $data['selstyle'], 'str:1:', 'plain');

        // experimental combine/compress css options
        $this->var()->find('combinecss', $data['combinecss'], 'checkbox', false);
        $this->var()->find('compresscss', $data['compresscss'], 'checkbox', false);
        // can't compress if not combined :)
        if ($data['combinecss'] == false) {
            $data['compresscss'] = false;
        }
        $this->var()->find('allowajax', $data['allowajax'], 'checkbox', false);

        $this->var()->find(
            'enable_user_menu',
            $data['enable_user_menu'],
            'checkbox',
            xarModVars::get('themes', 'enable_user_menu')
        );


        // Dashboard
        //    if (!isset($data['dashtemplate']) || trim($data['dashtemplate']=='')) {
        //        $data['dashtemplate']='dashboard';
        //    }

        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'themes']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();

        sys::import('modules.dynamicdata.class.properties.master');
        $data['user_themes'] = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
        $data['user_themes']->options = $adminapi->dropdownlist(['Class' => 2]);
        $data['user_themes']->setValue(xarModVars::get('themes', 'user_themes'));
        $data['user_themes']->layout = 'vertical';
        switch (strtolower($phase)) {
            case 'modify':
            default:
                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                $isvalid = $data['module_settings']->checkInput();
                $andvalid = ($data['enable_user_menu'] != false) ? $data['user_themes']->checkInput('user_themes') : true;

                if (!$isvalid || !$andvalid) {
                    $data['context'] ??= $this->getContext();
                    return xarTpl::module('themes', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                    xarModVars::set('themes', 'enable_user_menu', $data['enable_user_menu']);
                    if (isset($data['user_themes']->value)) {
                        xarModVars::set('themes', 'user_themes', $data['user_themes']->value);
                    }
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
                xarConfigVars::set(null, 'Site.BL.CacheTemplates', $data['cachetemplates']);
                xarConfigVars::set(null, 'Site.BL.MemCacheTemplates', $data['memcachetemplates']);
                xarConfigVars::set(null, 'Site.BL.CompressWhitespace', $data['compresswhitespace']);
                xarConfigVars::set(null, 'Site.BL.DocType', $data['doctype']);
                xarConfigVars::set(null, 'Site.BL.ExceptionDisplay', $data['exceptionsdisplay']);
                xarConfigVars::set(null, 'Site.Core.AllowAJAX', $data['allowajax']);
                xarModVars::set('themes', 'hidecore', $data['hidecore']);
                xarModVars::set('themes', 'selstyle', $data['selstyle']);
                xarModVars::set('themes', 'selfilter', $data['selfilter']);
                xarModVars::set('themes', 'selsort', $data['selsort']);

                // css combine/compress options
                xarModVars::set('themes', 'css.combined', $data['combinecss']);
                xarModVars::set('themes', 'css.compressed', $data['compresscss']);

                xarModVars::set('themes', 'debugmode', $data['debugmode']);

                sys::import('modules.dynamicdata.class.properties.master');
                $caches = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
                $caches->checkInput('flushcaches');
                xarModVars::set('themes', 'flushcaches', $caches->value);

                // Flush the caches
                $cachestoflush = $caches->getValue();
                /** @var FilePickerProperty $picker */
                $picker = DataPropertyMaster::getProperty(['name' => 'filepicker']);
                foreach ($cachestoflush as $cachetoflush) {
                    $picker->initialization_basedirectory = sys::varpath() . "/cache/" . $cachetoflush;
                    if (!file_exists($picker->initialization_basedirectory)) {
                        continue;
                    }
                    $files = $picker->getOptions();
                    foreach ($files as $file) {
                        unlink($picker->initialization_basedirectory . "/" . $file['id']);
                    }
                }

                xarController::redirect(xarController::URL('themes', 'admin', 'modifyconfig'), null, $this->getContext());
                return true;

            case 'flush':
                // Flush the cache directories
                sys::import('modules.dynamicdata.class.properties.master');
                $caches = DataPropertyMaster::getProperty(['name' => 'checkboxlist']);
                $caches->checkInput('flushcaches');
                xarModVars::set('themes', 'flushcaches', $caches->value);
                // Flush the caches
                $cachestoflush = $caches->getValue();
                /** @var FilePickerProperty $picker */
                $picker = DataPropertyMaster::getProperty(['name' => 'filepicker']);
                foreach ($cachestoflush as $cachetoflush) {
                    $picker->initialization_basedirectory = sys::varpath() . "/cache/" . $cachetoflush;
                    if (!file_exists($picker->initialization_basedirectory)) {
                        continue;
                    }

                    $dir = new RelativeDirectoryIterator($picker->initialization_basedirectory);

                    for ($dir->rewind();$dir->valid();$dir->next()) {
                        if ($dir->isDir()) {
                            continue;
                        } // no dirs
                        if ($dir->isDot()) {
                            continue;
                        } // skip . and ..
                        $name = $dir->getFileName();
                        if (strpos($name, '.') !== (int) 0) {
                            unlink($picker->initialization_basedirectory . "/" . $name);
                        }
                    }
                }

                // Empty the cache_data table in the database
                $caches->checkInput('flushdbcaches');
                xarModVars::set('themes', 'flushdbcaches', $caches->value);
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
}
