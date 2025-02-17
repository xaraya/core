<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the modules admin API
 *
 * @method mixed getSupportedDbs(array $args = []) Function return the database types give a middleware
 * @method mixed getmodulesettings(array $args = []) Get module settings for admin API
 * @method mixed getusersettings(array $args) Get user settings for admin API
 *  array{args: string, args: int}
 * @method mixed loadmenuarray(array $args) Utility function to get an array of menulinks from {modtype} getmenulinks function or {modtype}menu-dat.xml file - This function pays no respect to the active state of the module, calling functions must determine that - It looks for menu links for the specified module and type, falling back to the current request module and type - It first looks for links in xml files in ./code/modules/{modname}/xardata/{modtype}menu-dat.xml - If the xml file doesn't exist, it falls back to looking for links in the modules' getmenulinks function - If no links are found the function returns an empty array
 *  array{args?: string, args?: string, args: string, args?: bool, args?: bool}
 * @method mixed menuarray(array $args = []) Utility function to create an array for a getmenulinks function
 * @method mixed readFile(array $args = []) Function to read a file
 * @method mixed sanitizeFilename(array $args = [])
 * @method mixed waitingcontent(array $args = []) Call the waiting content hook
 * @method mixed writeFile(array $args = []) Function to write to a file
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
