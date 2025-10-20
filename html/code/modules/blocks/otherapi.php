<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\UserApiInterface;

/**
 * Trait to handle other api/gui functions
 */
trait OtherApiTrait
{
    /**
     * Get module blocks API class for this module
     */
    public function blocksapi(): ?UserApiInterface
    {
        $component = $this->getModule()->getComponent('BlocksApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }

    /**
     * Get module instances API class for this module
     */
    public function instancesapi(): ?UserApiInterface
    {
        $component = $this->getModule()->getComponent('InstancesApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }

    /**
     * Get module types API class for this module
     */
    public function typesapi(): ?UserApiInterface
    {
        $component = $this->getModule()->getComponent('TypesApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }
}
