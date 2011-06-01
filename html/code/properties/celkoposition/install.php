<?php
/**
 * CelkoPosition Property
 *
 * @package properties
 * @subpackage celkoposition property
 * @category Core Xaraya Property
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('properties.celkoposition.main');
sys::import('modules.dynamicdata.class.properties.interfaces');

class CelkoPositionPropertyInstall extends CelkoPositionProperty implements iDataPropertyInstall
{

    public function install(Array $data=array())
    {
        $dat_file = sys::code() . 'properties/celkoposition/data/configurations-dat.xml';
        $data = array('file' => $dat_file);
        try {
        $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
    
}

?>