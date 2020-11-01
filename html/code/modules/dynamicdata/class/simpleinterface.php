<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Johnny Robeson <johnny@localmomentum.net>
 */

sys::import('modules.dynamicdata.class.ui_handlers.default');
/**
  * Simple Object Interface
  */
class SimpleObjectInterface extends DataObjectDefaultHandler
{
    function __construct(array $args = array())
    {
        parent::__construct($args);
        if (!xarVar::fetch('tplmodule',   'isset', $args['tplmodule'], 'dynamicdata', xarVar::NOT_REQUIRED))
            return;

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
    }

    function handle(array $args = array())
    {
        if (!xarVar::fetch('method', 'str', $args['method'], 'showDisplay', xarVar::NOT_REQUIRED))
            return;
        if (!xarVar::fetch('itemid', 'id', $args['itemid'], NULL, xarVar::DONT_SET))
            return;
        // @todo maybe this should be done somewhere else ?
        if (!xarVar::fetch('qparam', 'str', $qparam, NULL, xarVar::DONT_SET))
           return;
        if (!xarVar::fetch('qstring', 'str', $qstring, NULL, xarVar::DONT_SET))
           return;

        if (!empty($qparam) && !empty ($qstring)) {
            $args['where'] = "$qparam LIKE '$qstring%'";
        }
        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        $this->object = DataObjectMaster::getObjectList($this->args);
        if (method_exists($this->object,$this->args['method'])) {
            $this->object->getItems();
        } else {
            $this->object = DataObjectMaster::getObject($this->args);
        }

        if (empty($this->object)) return;

        return $this->object->{$this->args['method']}($this->args);
    }
}
?>
