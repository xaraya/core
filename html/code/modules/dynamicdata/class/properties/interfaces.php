<?php
/**
 * Interfaces for Dynamic Properties
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

interface iDataProperty
{
    public function __construct(ObjectDescriptor $descriptor);
    public function checkInput($name = '', $value = null);
    public function fetchValue($name = '');
    // @checkme this shouldn't be here at all - see PropertyRegistration instead
    public function getValue();
    public function parseConfiguration($configuration = '');
    public function showConfiguration(array $data = []);
    public function updateConfiguration(array $data = []);
    public function setValue($value = null);
    public function showHidden(array $args = []);
    public function showInput(array $args = []);
    public function showLabel(array $args = []);
    //    CHECKME: public  or what?
    //    public function _showPreset(Array $args = array());
    public function showOutput(array $args = []);
    public function validateValue($value = null);
}

/**
 * Interfaces for Dynamic Property Installers
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
interface iDataPropertyInstall
{
    public function install(array $data = []);
}
