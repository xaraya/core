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
use FilePickerProperty;
use Query;
use RelativeDirectoryIterator;
use ixarMod;
use xarTpl;
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
        if (!$this->sec()->checkAccess('AdminThemes')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('sitename', $data['sitename'], 'str', $this->mod()->getVar('SiteName'));
        $this->var()->find('separator', $data['separator'], 'str:1:', $this->mod()->getVar('SiteTitleSeparator'));
        $this->var()->find('pagetitle', $data['pagetitle'], 'str:1:', 'default');
        $this->var()->find('showphpcbit', $data['showphpcbit'], 'checkbox', (bool) $this->mod()->getVar('ShowPHPCommentBlockInTemplates'));
        $this->var()->find('showtemplates', $data['showtemplates'], 'checkbox', (bool) $this->mod()->getVar('ShowTemplates'));
        $this->var()->find('cachetemplates', $data['cachetemplates'], 'checkbox', $this->config()->getVar('Site.BL.CacheTemplates'));
        $this->var()->find('memcachetemplates', $data['memcachetemplates'], 'checkbox', $this->config()->getVar('Site.BL.MemCacheTemplates'));
        $this->var()->find('variable_dump', $data['variable_dump'], 'checkbox', (bool) $this->mod()->getVar('variable_dump'));
        $this->var()->find('slogan', $data['slogan'], 'str', $this->mod()->getVar('SiteSlogan'));
        $this->var()->find('footer', $data['footer'], 'str', $this->mod()->getVar('SiteFooter'));
        $this->var()->find('copyright', $data['copyright'], 'str', $this->mod()->getVar('SiteCopyRight'));
        $this->var()->find('AtomTag', $data['atomtag'], 'str:1:', (bool) $this->mod()->getVar('AtomTag'));
        $this->var()->find('compresswhitespace', $data['compresswhitespace'], 'int', 0);
        $this->var()->find('doctype', $data['doctype'], 'str:1', 0);
        $this->var()->find('debugmode', $data['debugmode'], 'int', 0);
        $this->var()->find('exceptionsdisplay', $data['exceptionsdisplay'], 'int', 0);

        $this->var()->find('themedir', $data['defaultThemeDir'], 'str:1:', 'themes');
        $this->var()->find('adminpagemenu', $data['adminpagemenu'], 'checkbox', (bool) $this->mod()->getVar('adminpagemenu'));
        $this->var()->find('userpagemenu', $data['userpagemenu'], 'checkbox', (bool) $this->mod()->getVar('userpagemenu'));
        //    $this->var()->find('usedashboard', $data['usedashboard'], 'checkbox', (bool)$this->mod()->getVar('usedashboard'));
        //    $this->var()->find('dashtemplate', $data['dashtemplate'], 'str:1:', trim($this->mod()->getVar('dashtemplate')));

        $this->var()->find('selsort', $data['selsort'], 'str:1:', 'plain');
        $this->var()->find('selfilter', $data['selfilter'], 'int', ixarMod::STATE_ANY);
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
            $this->mod()->getVar('enable_user_menu')
        );


        // Dashboard
        //    if (!isset($data['dashtemplate']) || trim($data['dashtemplate']=='')) {
        //        $data['dashtemplate']='dashboard';
        //    }

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'themes']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons, enable_short_urls');
        $data['module_settings']->getItem();

        sys::import('modules.dynamicdata.class.properties.master');
        $data['user_themes'] = $this->prop()->getProperty(['name' => 'checkboxlist']);
        $data['user_themes']->options = $adminapi->dropdownlist(['Class' => 2]);
        $data['user_themes']->setValue($this->mod()->getVar('user_themes'));
        $data['user_themes']->layout = 'vertical';
        switch (strtolower($phase)) {
            case 'modify':
            default:
                break;

            case 'update':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                $isvalid = $data['module_settings']->checkInput();
                $andvalid = ($data['enable_user_menu'] != false) ? $data['user_themes']->checkInput('user_themes') : true;

                if (!$isvalid || !$andvalid) {
                    $data['context'] ??= $this->getContext();
                    return $this->tpl()->module('themes', 'admin', 'modifyconfig', $data);
                } else {
                    $itemid = $data['module_settings']->updateItem();
                    $this->mod()->setVar('enable_user_menu', $data['enable_user_menu']);
                    if (isset($data['user_themes']->value)) {
                        $this->mod()->setVar('user_themes', $data['user_themes']->value);
                    }
                }
                $this->mod()->setVar('SiteName', $data['sitename']);
                $this->mod()->setVar('SiteTitleSeparator', $data['separator']);
                $this->mod()->setVar('SiteTitleOrder', $data['pagetitle']);
                $this->mod()->setVar('SiteSlogan', $data['slogan']);
                $this->mod()->setVar('SiteCopyRight', $data['copyright']);
                $this->mod()->setVar('SiteFooter', $data['footer']);
                $this->mod()->setVar('ShowPHPCommentBlockInTemplates', $data['showphpcbit']);
                $this->mod()->setVar('ShowTemplates', $data['showtemplates']);
                $this->mod()->setVar('AtomTag', $data['atomtag']);
                $this->mod()->setVar('variable_dump', $data['variable_dump']);
                $this->mod()->setVar('adminpagemenu', $data['adminpagemenu']);
                $this->mod()->setVar('userpagemenu', $data['userpagemenu']);
                //            $this->mod()->setVar('usedashboard', $data['usedashboard']);
                //            $this->mod()->setVar('dashtemplate', $data['dashtemplate']);
                // <chris/> Instead of setting the base theme config var dir directly,
                // let xarTpl take care of it, it'll complain if the directory doesn't
                // exist or the current theme isn't in the directory specified
                // $this->config()->setVar('Site.BL.ThemesDirectory', $data['defaultThemeDir']);
                xarTpl::setBaseDir($data['defaultThemeDir']);
                $this->config()->setVar('Site.BL.CacheTemplates', $data['cachetemplates']);
                $this->config()->setVar('Site.BL.MemCacheTemplates', $data['memcachetemplates']);
                $this->config()->setVar('Site.BL.CompressWhitespace', $data['compresswhitespace']);
                $this->config()->setVar('Site.BL.DocType', $data['doctype']);
                $this->config()->setVar('Site.BL.ExceptionDisplay', $data['exceptionsdisplay']);
                $this->config()->setVar('Site.Core.AllowAJAX', $data['allowajax']);
                $this->mod()->setVar('hidecore', $data['hidecore']);
                $this->mod()->setVar('selstyle', $data['selstyle']);
                $this->mod()->setVar('selfilter', $data['selfilter']);
                $this->mod()->setVar('selsort', $data['selsort']);

                // css combine/compress options
                $this->mod()->setVar('css.combined', $data['combinecss']);
                $this->mod()->setVar('css.compressed', $data['compresscss']);

                $this->mod()->setVar('debugmode', $data['debugmode']);

                sys::import('modules.dynamicdata.class.properties.master');
                $caches = $this->prop()->getProperty(['name' => 'checkboxlist']);
                $caches->checkInput('flushcaches');
                $this->mod()->setVar('flushcaches', $caches->value);

                // Flush the caches
                $cachestoflush = $caches->getValue();
                /** @var FilePickerProperty $picker */
                $picker = $this->prop()->getProperty(['name' => 'filepicker']);
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

                $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'modifyconfig'));
                return true;

            case 'flush':
                // Flush the cache directories
                sys::import('modules.dynamicdata.class.properties.master');
                $caches = $this->prop()->getProperty(['name' => 'checkboxlist']);
                $caches->checkInput('flushcaches');
                $this->mod()->setVar('flushcaches', $caches->value);
                // Flush the caches
                $cachestoflush = $caches->getValue();
                /** @var FilePickerProperty $picker */
                $picker = $this->prop()->getProperty(['name' => 'filepicker']);
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
                $this->mod()->setVar('flushdbcaches', $caches->value);
                $cachestoflush = $caches->getValue();
                sys::import('xaraya.structures.query');
                foreach ($cachestoflush as $cachetoflush) {
                    if ($cachetoflush == 'dynamicdata') {
                        $q = new Query('DELETE', $this->db()->getPrefix() . '_cache_data');
                        $q->run();
                    }
                }
                break;
        }
        return $data;
    }
}
