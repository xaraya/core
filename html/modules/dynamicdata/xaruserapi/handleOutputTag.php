<?php

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-output field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-output property="$property" /> with $property a Dynamic Property object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showoutput() in the BL template
 */
function dynamicdata_userapi_handleOutputTag($args)
{
    if (!empty($args['property'])) {
        if (isset($args['value'])) {
            if (is_numeric($args['value']) || substr($args['value'],0,1) == '$') {
                return 'echo '.$args['property'].'->showOutput('.$args['value'].'); ';
            } else {
                return 'echo '.$args['property'].'->showOutput("'.$args['value'].'"); ';
            }
        } else {
            return 'echo '.$args['property'].'->showOutput(); ';
        }
    }

    $out = "echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showoutput',\n";
    if (isset($args['field'])) {
        $out .= '                   '.$args['field']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

?>
