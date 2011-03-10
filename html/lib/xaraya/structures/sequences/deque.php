<?php

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

    // Pop an item off the Deque, head or tail
    public function &pop($whichEnd)
    {
        $position = $this->__get($whichEnd);
        $item = parent::get($position);
        if($item == null) return $item;
        parent::delete($whichEnd);
        return $item;
    }

    public function clear()
    {
        return parent::clear();
    }
}
?>
