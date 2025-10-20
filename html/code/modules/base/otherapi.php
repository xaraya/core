<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\UserApiInterface;

/**
 * Trait to handle other api/gui functions
 */
trait OtherApiTrait
{
    /**
     * Get module javascript API class for this module
     */
    public function javascriptapi(): ?UserApiInterface
    {
        $component = $this->getModule()->getComponent('JavascriptApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }

    /**
     * Get module ws API class for this module
     */
    public function wsapi(): ?UserApiInterface
    {
        $component = $this->getModule()->getComponent('WsApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }
}
