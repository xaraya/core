<?php

/**
 * Finclude Block display interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
/**
 * Display block
 *
 * @author Patrick Kellum
 */
class Base_FincludeBlockDisplay extends Base_FincludeBlock implements iBlock
{
    /**
     * Disaply function
     *
     * @return array<mixed> Retursn display data array
     */
    public function display()
    {
        $data = $this->getContent();
        if (empty($this->url)) {
            $data['url'] = $this->ml('Block has no file defined to include');
        } else {
            if (!file_exists($this->url)) {
                $data['url'] = $this->ml('Warning: File to include does not exist. Check file definition in finclude block instance.');
            } else {
                $data['url'] = file_get_contents($this->url);
            }
        }
        return $data;
    }
}
