<?php

class BasicCollection extends DataContainer implements Collection
{
    protected $elements;

    public function __construct()
    {
        $this->elements = array();
    }
    public function add(Object $element)
    {
        $this->elements[$element->hash()] = $element;
    }
    public function addAll(BasicCollection $collection)
    {
        $this->elements = array_merge($this->elements,$collection->toArray());
    }
    public function clear()
    {
        $this->elements = array();
    }
    public function isEmpty()
    {
        return count($this->elements) == 0;
    }
    public function remove(Object $element)
    {
        unset($this->elements[$element->hash()]);
    }
    public function removeAll(BasicCollection $collection)
    {
        foreach($collection->toArray() as $key => $value)
            if (in_array($value,$this->elements)) unset($this->elements[$key]);
    }
    public function size()
    {
        return count($this->elements);
    }
    public function toArray()
    {
        return $this->elements;
    }
}
class BasicSet extends BasicCollection implements IteratorAggregate
{
    public function getIterator()
    {
        $arrayobj = new ArrayObject($this->elements);
        return $arrayobj->getIterator();
    }
}

interface Collection
{
    public function add(Object $element);
    public function addAll(BasicCollection $collection);
    public function clear();
    public function isEmpty();
    public function remove(Object $element);
    public function removeAll(BasicCollection $collection);
    public function size();
    public function toArray();
    public function toString();
}
?>
