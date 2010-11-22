<?php
    function privileges_user_errors()
    {
        if(!xarVarFetch('layout',   'isset', $data['layout']   , 'default', XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('redirecturl',   'isset', $data['redirecturl']   , xarServer::getCurrentURL(array(),false), XARVAR_DONT_SET)) {return;}
        if (!xarUserIsLoggedIn()) {
            return $data;
        } else {
            xarController::redirect($data['redirecturl']);
            return true;
        }
    }
?>