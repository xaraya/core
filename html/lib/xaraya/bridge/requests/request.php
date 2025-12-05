<?php

/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

use Xaraya\Context\WithContextInterface;
use Xaraya\Context\WithContextTrait;

/**
 * Handle generic requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 * Accepts PSR-7 compatible server requests, xarRequest (partial use), context or nothing (using $_SERVER)
 */
class BasicRequest implements CommonRequestInterface, WithContextInterface
{
    use CommonRequestTrait;
    use WithContextTrait;
}
