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
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin themesinfo function
 * @extends MethodClass<AdminGui>
 */
class ThemesinfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View complete theme information/details
     * function passes the data to the template
     * @author Marty Vance
     * @access public
     * @return array|string|void data for the template display
     * @todo some facelift
     * @see AdminGui::themesinfo()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditThemes')) {
            return;
        }

        $data = [];

        $this->var()->find('id', $themeid, 'int:1:', 0);
        $this->var()->check('exit', $exit);
        $this->var()->check('confirm', $confirm);
        if (empty($themeid)) {
            return $this->ctl()->notFound();
        }

        // obtain maximum information about a theme
        $info = xarTheme::getInfo($themeid);

        // get the theme object corresponding to this theme
        sys::import('modules.dynamicdata.class.objects.factory');
        $theme = $this->data()->getObject(['name'   => 'themes']);
        $id = $theme->getItem(['itemid' => $info['systemid']]);
        if (empty($theme)) {
            return;
        }

        $data['theme'] = $theme;
        $data['themeid'] = $themeid;
        $data['properties'] = $theme->properties;

        if ($confirm || $exit) {

            // Check for a valid confirmation key
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['theme']->properties['configuration']->checkInput();
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return $this->tpl()->module('themes', 'admin', 'themesinfo', $data);
            } else {
                // Good data: create the item
                $itemid = $data['theme']->updateItem(['itemid' => $info['systemid']]);

                // Jump to the next page
                if ($exit) {
                    $this->ctl()->redirect($this->ctl()->getModuleURL('themes', 'admin', 'view'));
                } else {
                    $this->ctl()->redirect($this->ctl()->getModuleURL(
                        'themes',
                        'admin',
                        'themesinfo',
                        ['id' => $themeid]
                    ));
                }
                return true;
            }
        }

        $data['themename']            = \xarVarPrep::forDisplay($info['name']);
        $data['themedescr']           = \xarVarPrep::forDisplay($info['description']);
        //$data['themedispname']        = \xarVarPrep::forDisplay($themeinfo['displayname']);
        $data['themelisturl']         = $this->ctl()->getModuleURL('themes', 'admin', 'view');

        $data['themedir']             = \xarVarPrep::forDisplay($info['directory']);
        $data['themeclass']           = \xarVarPrep::forDisplay($info['class']);
        $data['themever']             = \xarVarPrep::forDisplay($info['version']);
        $data['themestate']           = $info['state'];
        $data['themeauthor']          = preg_replace('/,/', '<br />', \xarVarPrep::forDisplay($info['author']));
        if (!empty($info['dependency'])) {
            $dependency             = $this->ml('Working on it...');
        } else {
            $dependency             = $this->ml('None');
        }
        $data['themedependency']      = \xarVarPrep::forDisplay($dependency);

        return $data;
    }
}
