<?php

/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * @author the Example module development team
 * @param $params array containing the different elements of the virtual path
 * @returns array
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function base_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();

    // Analyse the different parts of the virtual path
    // $params[1] contains the first part after index.php/example

    // In general, you should be strict in encoding URLs, but as liberal
    // as possible in trying to decode them...

    if (empty($params[1])) {
        // nothing specified -> we'll go to the main function
        return array('main', $args);

    } elseif (is_string($params[1])) {
        // this must be some page here
        // Note : make sure your encoding/decoding is consistent ! :-)
        $page = $params[1];
        $args['page'] = $page;
        return array('main', $args);

    } else {
        // we have no idea what this virtual path could be, so we'll just
        // forget about trying to decode this thing

        // you *could* return the main function here if you want to
        //return array('main', $args);
    }

    // default : return nothing -> no short URL decoded
}

?>
