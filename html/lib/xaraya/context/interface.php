<?php
/**
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Context;

interface ContextInterface
{
    /**
     * Get current requestId
     * @return mixed
     */
    public function getRequestId();

    /**
     * Get current session (if any)
     * @return mixed
     */
    public function getSession();

    /**
     * Get current userId
     * @return mixed
     */
    public function getUserId();

    /**
     * Set current userId
     * @param mixed $userId
     * @return void
     */
    public function setUserId($userId);
}
