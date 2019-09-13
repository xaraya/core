<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

sys::import('xaraya.structures.sequences.interfaces');
sys::import('xaraya.structures.sequences.adapters.sequence_adapter');

// A deque can be manipulated at both ends

/**
 * Implementation of the Deque datastructure
 *
 */
class Deque extends SequenceAdapter implements iDeque
{
    // Push an item into the Deque, head or tail
    public function push($item, $whichEnd) 
    {
        $position = $this->__get($whichEnd);
        return parent::insert($item,$position);
    }

    // Peek at an item at the head or tail of the Deque
    public function &peek($whichEnd)
    {
        $position = $this->__get($whichEnd);
        if ($position < 0) {
            $item = null;
        } else {
            $item = parent::get($position);
        }
        return $item;
    }

    // Pop an item off the Deque, head or tail
    public function &pop($whichEnd)
    {
        $item = $this->peek($whichEnd);var_dump($whichEnd);
        if ($item == null) return $item;
        $position = $this->__get($whichEnd);
        parent::delete($position);
        return $item;
    }

    public function clear()
    {
        return parent::clear();
    }
}
?>
