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
use DataObjectFactory;
use xarController;
use xarSec;
use xarSecurity;
use xarTheme;
use xarTpl;
use xarVar;
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
        if (!xarSecurity::check('EditThemes')) {
            return;
        }

        $data = [];

        $this->var()->find('id', $themeid, 'int:1:', 0);
        $this->var()->check('exit', $exit);
        $this->var()->check('confirm', $confirm);
        if (empty($themeid)) {
            return xarController::notFound(null, $this->getContext());
        }

        // obtain maximum information about a theme
        $info = xarTheme::getInfo($themeid);

        // get the theme object corresponding to this theme
        sys::import('modules.dynamicdata.class.objects.factory');
        $theme = DataObjectFactory::getObject(['name'   => 'themes']);
        $id = $theme->getItem(['itemid' => $info['systemid']]);
        if (empty($theme)) {
            return;
        }

        $data['theme'] = $theme;
        $data['themeid'] = $themeid;
        $data['properties'] = $theme->properties;

        if ($confirm || $exit) {

            // Check for a valid confirmation key
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['theme']->properties['configuration']->checkInput();
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return xarTpl::module('themes', 'admin', 'themesinfo', $data);
            } else {
                // Good data: create the item
                $itemid = $data['theme']->updateItem(['itemid' => $info['systemid']]);

                // Jump to the next page
                if ($exit) {
                    xarController::redirect(xarController::URL('themes', 'admin', 'view'), null, $this->getContext());
                } else {
                    xarController::redirect(xarController::URL(
                        'themes',
                        'admin',
                        'themesinfo',
                        ['id' => $themeid]
                    ), null, $this->getContext());
                }
                return true;
            }
        }

        $data['themename']            = xarVar::prepForDisplay($info['name']);
        $data['themedescr']           = xarVar::prepForDisplay($info['description']);
        //$data['themedispname']        = xarVar::prepForDisplay($themeinfo['displayname']);
        $data['themelisturl']         = xarController::URL('themes', 'admin', 'view');

        $data['themedir']             = xarVar::prepForDisplay($info['directory']);
        $data['themeclass']           = xarVar::prepForDisplay($info['class']);
        $data['themever']             = xarVar::prepForDisplay($info['version']);
        $data['themestate']           = $info['state'];
        $data['themeauthor']          = preg_replace('/,/', '<br />', xarVar::prepForDisplay($info['author']));
        if (!empty($info['dependency'])) {
            $dependency             = xarML('Working on it...');
        } else {
            $dependency             = xarML('None');
        }
        $data['themedependency']      = xarVar::prepForDisplay($dependency);

        return $data;
    }
}
