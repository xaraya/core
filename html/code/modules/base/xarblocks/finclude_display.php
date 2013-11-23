<?php
/**
 * Finclude Block display interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Display block
 *
 * @author Patrick Kellum
 */
sys::import('modules.base.xarblocks.finclude');
class Base_FincludeBlockDisplay extends Base_FincludeBlock implements iBlock
{

    /**
     * Disaply function
     * 
     * @param void N/A
     * @return array Retursn display data array
     */
    function display()
    {
        $data = $this->getContent();
        if (empty($this->url)) {
            $data['url'] = xarML('Block has no file defined to include');
        } else {
            if (!file_exists($this->url)) {
                $data['url'] = xarML('Warning: File to include does not exist. Check file definition in finclude block instance.');
            } else {
                $data['url'] = implode(file($this->url), '');
            }
        }
        return $data;
    }
}
?>