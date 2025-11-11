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

/**
 * Handle the base ws API
 *
 * @method mixed default(array $args = []) Default web suervices call
 * @extends UserApiClass<Module>
 */
class WsApi extends UserApiClass
{
    public function configure()
    {
        $this->setModType('ws');
        // don't call xar::mod()->apiLoad() for base ws API
    }
}
