<?php

/** 
 * External entity resolver
 *
 * This handlers takes xml data and inserts the content
 * of all referenced external entities into the xml document 
 * producing a new xml document
 *
 */
class xarXmlEntityResolve extends xarXmlCopyTransform
{
    function external_entity_reference($parser, $entity_names,  $resolve_base, $system_id, $public_id)
    {
        $entity_content = '';
        //echo "External entity ref handler\n";
        if($system_id) {
            // FIXME: I don't know the logic when to use public id and when to use system_id
            //        for now i only use system_id, which is a filename.
            // system_id is a filename, and as the $resolve_base is always empty we have to cope here
            if(!file_exists($system_id)) {
                // couldn't find it directly through absolute reference, try relative
                // if that doesn't help, the parser will raise an error for us
                if($this->_resolve_base) $system_id=$this->_resolve_base ."/". $system_id;
            } 
            if(!file_exists($system_id)) return false;

            // External entities may be empty
            if(filesize($system_id) != 0) {
                $fp = fopen($system_id,"r");
                $entity_content = fread($fp,filesize($system_id));
                fclose($fp);
            }
        }
        // What to do with this content?
        $this->output .= $content;
    }     
}

?>