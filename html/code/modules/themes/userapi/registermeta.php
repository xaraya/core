<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\UserApi;
use xarMeta;

/**
 * themes userapi registermeta function
 * @extends MethodClass<UserApi>
 */
class RegistermetaMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Register function
     * Register meta data in queue for later rendering
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string $args[type] the type of meta tag, either name or http-equiv, required<br/>
     * string $args[value] the value of the type, eg (author, rating, refresh, etc..), required<br/>
     * string $args[content] the meta content, required<br/>
     * string $args[lang] the ISO 639-1 language code for the content, optional<br/>
     * string $args[dir] the text direction of the content (ltr|rtl), optional<br/>
     * string $args[scheme] the scheme used to interpret the content, optional
     * @return bool true on success
     * @see UserApi::registermeta()
     */
    public function __invoke(array $args = [])
    {
        $meta = xarMeta::getInstance($this->getParent());
        return $meta->register($args);
    }
}
