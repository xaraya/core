<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\UserGuiClass;

/**
 * Handle the base user GUI
 *
 * @method mixed errors(array $args = []) Entry point for custom error messages - Use this for redirecting pages from other applications or within Xaraya
 * @method mixed main(array $args = []) The main user interface function of this module.
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    // ...
}
