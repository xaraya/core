<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the themes admin API
 *
 * @method mixed activate(array $args = []) Activate a theme if it has an active function, otherwise just set the state to active
 * @method mixed countitems(array $args = [])
 * @method mixed dropdownlist(array $args = [])
 * @method mixed getJsLibs(array $args = [])
 * @method mixed getdbthemes(array $args = []) Get all themes in the database
 * @method mixed getfilethemes(array $args = []) Get themes from filesystem
 * @method mixed getitems(array $args = [])
 * @method mixed getlist(array $args = []) Gets a list of themes that matches required criteria.
 * @method mixed getthemelist(array $args = []) Gets a list of themes that matches required criteria - Supported criteria are: UserCapable, AdminCapable, Class, Category, State.
 * @method mixed initialise(array $args = []) Initialise a theme
 * @method mixed install(array $args = []) Install a theme.
 * @method mixed list(array $args = []) Obtain list of themes
 * @method mixed regenerate(array $args = []) Regenerate theme list
 * @method mixed remove(array $args = []) Remove a theme
 * @method mixed setstate(array $args = []) Set the state of a theme
 * @method mixed themedir2name(array $args = []) Convert a theme directory to a theme name.
 * @method mixed upgrade(array $args = []) Upgrade a theme
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
