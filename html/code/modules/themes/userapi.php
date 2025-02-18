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

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the themes user API
 *
 * @method mixed deliver(array $args = []) Handle place-css tag
 * @method mixed dropdownlist(array $args = [])
 * @method mixed getimage(array $args = []) Get image - Wrapper for the <xar:img .../> template tag and xarTpl::getImage function
 * @method mixed register(array $args = []) Handle css tag
 * @method mixed registerjs(array $args = []) Registerjs function - Register javascript in the queue for later rendering
 * @method mixed registermeta(array $args = []) Register function - Register meta data in queue for later rendering
 * @method mixed renderjs(array $args = []) Renderjs function - Render queued javascript
 * @method mixed rendermeta(array $args = []) Render meta function - Render queued meta tags
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...
}
