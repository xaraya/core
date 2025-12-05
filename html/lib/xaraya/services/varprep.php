<?php

/**
 * VarPrep available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarVarPrep;

/**
 * For documentation purposes only - available via VarPrepTrait
 */
interface VarPrepInterface extends WrapperInterface
{
    public const SLICE = 'varprep';
}

/**
 * VarPrep available via methods
 */
trait VarPrepTrait
{
    use WrapperTrait;
}

/**
 * Access xarVarPrep::* methods (text, html, ...)
 *
 * Available methods:
 * - text()
 * - html()
 * - email()
 * - path()
 * - validate()
 */
class VarPrepService implements VarPrepInterface
{
    use VarPrepTrait;
}
