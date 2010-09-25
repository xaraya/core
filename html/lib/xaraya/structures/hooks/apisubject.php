<?php
/**
 * Hook API Subject
 *
 * Handles api type hook observers (these typically return array of $extrainfo)
 *
 * NOTE: this class is never called directly, but should be extended
 * by api type hook subjects. Hook subjects should only need to extend this
 * class and overload the $subject property with their event subject name,
 * the inherited methods will take care of the rest
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.subject');
abstract class ApiHookSubject extends HookSubject implements ixarEventSubject
{
    public $subject = 'ApiHook';  // change this to the name of your hook subject
    /**
     * Notify hooked observers
     * @todo: it shouldn't be necessary to overload this method, make it final?
     *
     * @params none
     * @throws none
     * @return array of cumulative extrainfo from observers
    **/
    public function notify()
    {
        foreach ($this->observers as $obs) {
            try { 
                // notify observer and capture response 
                $extrainfo = $obs->notify($this);
                // api type hooks expect an array of extrainfo from each observer
                if (!empty($extrainfo) && is_array($extrainfo)) {
                    // update extrainfo for next observer 
                    $this->setArgs(array('extrainfo' => $extrainfo));
                }
            } catch (Exception $e) {
                // hooks shouldn't fail, ever!
                continue;
            }
        }
        return $this->getExtrainfo();
    }
}
?>