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

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the base javascript API
 *
 * @method mixed findfile(array $args = []) Base JavaScript management functions - Find the path for a JavaScript file.
 * @method mixed geteventattributes(array $args = []) Handle render javascript form field tags - Get JavaScript event attributes for a tag.
 * @method mixed geteventjs(array $args = []) Handle render javascript form field tags - Get JavaScript for a tag event.
 * @method mixed handleeventjavascript(array $args = []) Handle render javascript form field tags - Handle <xar:base-trigger-javascript ...> form field tags - Format : <xar:place-javascript definition="$definition"/> with $definition an array -       or <xar:place-javascript position="head|body|whatever|" type="code|src|whatever|"/> - Default position is ''; default type is ''.
 * @method mixed modulecode(array $args = []) Base JavaScript management functions - Include a module JavaScript link in a page.
 * @method mixed modulefile(array $args = []) Base JavaScript management functions - Include a module JavaScript link in a page.
 * @method mixed moduleinline(array $args = []) Base JavaScript management functions - Include a section of inline JavaScript code in a page.
 * @extends UserApiClass<Module>
 */
class JavascriptApi extends UserApiClass
{
    use OtherApiTrait;

    public function configure()
    {
        $this->setModType('javascript');
        // don't call xarMod:apiLoad() for base javascript API
    }
}
