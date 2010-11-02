<?php
/**
 * @package core
 * @subpackage structures
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * 
 */
sys::import('xaraya.structures.sequences.interfaces');
sys::import('xaraya.structures.sequences.adapters.sequence_adapter');

/**
 * A stack manipulates only the item at the head of the sequence
 *
 */
class Stack extends SequenceAdapter implements iStack
{
    public function push($item)
    {
        return $this->insert($item, $this->head);
    }

    public function &pop()
    {
        $item = null;
        if($this->empty) return $item;
        $item = parent::get($this->head);
        if($item == null) return $item;
        parent::delete($this->head);
        return $item;
    }
    
    public function clear()
    {
        parent::clear();
    }

    public function peek()
    {
        $item = null;
        if($this->empty) return $item;
        $item = $this->pop();
        $this->push($item);
        return $item;
    }
}
?>
