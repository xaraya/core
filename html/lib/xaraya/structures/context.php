<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Structures;

use Xaraya\Authentication\Usercontext;
use ArrayObject;
use sys;

sys::import('modules.authsystem.class.usercontext');

/**
 * Context object for request etc.
 * @template TKey of array-key
 * @template TValue of mixed
 * @extends ArrayObject<TKey, TValue>
 */
class Context extends ArrayObject
{
    /**
     * Get current request
     * @return mixed
     */
    public function getRequest()
    {
        return $this->offsetGet('request');
    }

    /**
     * Get current userId
     * @return mixed
     */
    public function getUserId()
    {
        if (!$this->offsetExists('userId')) {
            $userContext = new UserContext($this);
            $userId = $userContext->getUserId();
            $this->offsetSet('userId', $userId);
        }
        return $this->offsetGet('userId');
    }
}
