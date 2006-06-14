<?php
/**
 * Sequence implemented as a dd object, very inefficient implementation for now.
 *
 * 
 */
include_once dirname(__FILE__).'/array_sequence.php';
class DynamicDataSequence extends ArraySequence implements iSequence, iSequenceAdapter
{
    private $seqInfo   = null; /* This stays the same more or less */
    private $seqObject = null; /* The object definition */

    /* Construct the dd sequence
     * 
     * @param $args['name'] string name of the object containing the sequence
     *
     */ 
    public function __construct($args)
    {
        // TODO: check the object definition, it needs id, data and nextid
        assert('isset($args["name"]); /* To construct a dd sequence, an objectname must be passed in */');
        $this->seqInfo = xarModApiFunc('dynamicdata','user','getobjectinfo',$args);
         // This fills $seqObject and $seq with most current data.
        $this->getSequence();
    }

    /* Implementation of iSequence */

    /* Get an item from the sequence */
    public function &get($position) 
    {
        $item = null;
        if($position >  $this->tail) return $item;
        if($position == $this->head) $position = 0;
        $params = array('modid'     => $this->seqInfo['moduleid'],
                        'itemtype'  => $this->seqInfo['itemtype'],
                        'fieldlist' => array('data'),
                        'where'    => 'id = '.$this->items[$position]['id']);
        // And get the data, we do this explicitly because the 'data' field might be very big
        // so it is not included in the items property for this object by default.
        $item = xarModApiFunc('dynamicdata','user','getitems',$params);
        $item = $item[$this->items[$position]['id']]['data'];
        $item = unserialize(base64_decode($item));
        return $item;
    }

    /* Insert an item into the sequence at a certain position */
    public function insert(&$item, $position)
    {
        // Make sure position is in range
        if($position >  $this->tail) return false;
        if($position == $this->head) $position=0;
        
        $params['data'] = base64_encode(serialize($item));
        $params['nextid'] = -1;
        if($this->empty) {
            // We can just add it if there are no other items
            $newId = $this->seqObject->createItem($params);
        } else {
            // Insert the new item, with nextid pointing to the id of the
            // item currently at position N 
            $IDn = $this->items[$position]['id']; // This should always exist by now
            $params['nextid'] = $this->items[$position]['id'];
            $newID = $this->seqObject->createItem($params);

            // Update the item at N-1 (if any) with the new ID of the inserted item
            if($position > 0 && isset($newID)) {
                $this->setNextId($this->items[$position-1]['id'],$newId);
            }
        }
        // Data changed, refresh the sequence
        $this->getSequence();
        return true;
    }
    /* Delete an item from the sequence at a certain position */
    public function delete($position) 
    {
        if($position > $this->tail) return false;
        if($this->empty) return true;

        // Delete the item at that position
        $this->seqObject->deleteItem(array('itemid'=>$this->items[$position]['id']));

        // Link the nextid of Item_n-1 to the id of Item_n+1
        if(isset($this->items[$position-1])) {
            // There is a previous item to set
            $IDnpls1 = -1;
            if(isset($this->items[$position+1])) {
                // There is also a next item
                $IDnpls1 = $this->items[$position+1]['id'];
            }
            $res = $this->setNextId($this->items[$position-1]['id'],$IDnpls1);
        }
        // Data changed, refresh the sequence
        $this->getSequence();
        return true;
    }

    /* Clear the sequence */
    public function clear() 
    {
        if($this->empty) return true;
        if(!$this->items) return true; // CHECK THIS
        foreach($this->items as $index => $values) {
            $this->seqObject->deleteItem(array('itemid'=>$values['id']));
        }
        // Data changed, refresh the sequence
        $this->getSequence();
        return true;
    }
    /* End implementation of iSequence */
    
    /* Private helper function */
    /* Refresh the sequence data */
    private function &getSequence()
    {
        $this->seqObject = xarModApiFunc('dynamicdata','user','getobject',$this->seqInfo);
        $params = array('modid'     => $this->seqInfo['moduleid'],
                        'itemtype'  => $this->seqInfo['itemtype'],
                        'sort'      => 'nextid',
                        'fieldlist' => array('id','nextid'));
        // And get the data
        $objectData = xarModApiFunc('dynamicdata','user','getitems',$params);
        // Make sure we have them in the right order (logically), i.e. sort on nextid
        $this->items = array_reverse($objectData);
    }
    
    /* Update an item to have a new successor in the sequence */
    private function setNextId($itemid, $nextid)
    {
        $params = array('modid'     => $this->seqInfo['moduleid'],
                        'itemtype'  => $this->seqInfo['itemtype'],
                        'itemid'    => $itemid,
                        'fields'    => array(array('name'=>'nextid','value'=>$nextid)));
        
        $res = xarModApiFunc('dynamicdata','admin','update',$params);
        return $res;
    }
}
?>