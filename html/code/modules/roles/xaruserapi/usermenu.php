<?php
/**
 * Handle roles_user_settings object
 *
 * @package modules
 * @subpackage roles module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Provides extra processing to roles user account function for user_settings
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param $args['id'] id of the user to get settings for (default current user)
 * @param $args['phase'] phase to process (valid phases are showform, checkinput, and updateitem)
 * NOTE: If you provide this function in your module, you must include return values for all phases
 * @param $args['object'] user_settings object (default roles_user_settings)
 * @returns mixed
 * @return array on showform
 * @return bool on checkinput, invalid = false, valid = true
 * @return bool on updateitem, error = false, success = true
 */
function roles_userapi_usermenu($args)
{
    // not logged in?
    if (!xarUserIsLoggedIn()){
        // redirect user to their account page after login
        $redirecturl = xarModURL('roles', 'user', 'account');
        xarController::redirect(xarModURL($defaultloginmodname,'user','showloginform', array('redirecturl' => $redirecturl)));
    }

    // edit account is disabled?
    if ((bool)xarModVars::get('roles', 'usereditaccount') == false) {
        // show the user their profile display
        xarController::redirect(xarModURL('roles', 'user', 'account'));
    }

    // Get arguments from argument array
    extract($args);

    $data = array();

    if (empty($phase))
        $phase = 'showform';

    if (empty($id) || !is_numeric($id))
        $id = xarUserGetVar('id');

    if (!isset($object)) {
        $object = xarMod::apiFunc('base', 'admin', 'getusersettings', array('module' => 'roles', 'itemid' => $id));
    }
    // only get the fields we need
    $fieldlist = array();
    $settings = explode(',',xarModVars::get('roles', 'duvsettings'));
    if ((bool)xarModVars::get('roles', 'allowemail')) {
        $fieldlist[] = 'allowemail';
    }
    /* revisit in php5.3.0
    if (in_array('usertimezone', $settings)) {
        $fieldlist[] = 'usertimezone';
    }
    */
    if (in_array('userhome', $settings) && (bool)xarModVars::get('roles', 'allowuserhomeedit')) {
        $fieldlist[] = 'userhome';
    }
    if (in_array('useremailformat', $settings)) {
        $fieldlist[] = 'useremailformat';
    }
    $object->setFieldList(join(',',$fieldlist));
    switch (strtolower($phase)) {

        /**
         * The showform phase is called whenever the usermenu form is displayed
         * This data is passed to /roles/xartemplates/objects/showform-usermenu.xt
         * Use this phase to perform any extra operations on your data
         * (such as setting fieldlist, template, layout, etc, see below for examples)
        **/
        case 'showform':
        default:
            // optionally specify the module template and layout to use
            $object->tplmodule = 'roles'; // roles/xartemplates/objects/
            $object->template = 'usermenu'; // showform-usermenu.xt
            $object->layout = 'roles_user_settings';
            $object->getItem(array('itemid' => $id));

            // pass all relevant data back to the calling function
            // the object to be displayed
            $data['object'] = $object;
            // any extra data needed can be added to the $formdata array. This will be
            // available in your showform- template as #$formdata#. Use this if
            // your form needs data not available from the object itself.
            $data['formdata'] = array(
                'settings' => $settings
            );
            // if you want to provide your own update function, you can specify
            // the form action url to be used. When the form is POSTed your function
            // will be used. (see roles user usermenu for an example).
            $data['formaction'] = xarModURL('roles', 'user', 'usermenu');
            // not necessary, but for completeness pass back any fields you changed
            $data['tplmodule'] = 'roles';
            $data['template'] = 'usermenu';
            $data['layout'] = 'roles_user_settings';
            // pass the module name in when setting the authkey, this avoids clashes
            // when the output contained within another modules display (eg in xarpages)
            $data['authid'] = xarSecGenAuthKey('roles');
            // finally return data to the calling function
            return $data;
        break;

        /**
         * The checkinput phase allows you to perform validations. Use this
         * when a standard $object->checkInput() call just isn't enough.
        **/
        case 'checkinput':
            $isvalid = $object->checkInput();

            if (!empty($object->properties['userhome']) && (bool)xarModVars::get('roles','allowuserhomeedit')) {
               $home = $object->properties['userhome']->getValue();
               if ((bool)xarModVars::get('roles','allowuserhomeedit')) { // users can edit user home
                    // Check if external urls are allowed in home page
                    $allowexternalurl = (bool)xarModVars::get('roles','allowexternalurl');
                    $url_parts = parse_url($home);
                    if (!$allowexternalurl) {
                        if ((preg_match("%^http://%", $home, $matches)) &&
                        ($url_parts['host'] != $_SERVER["SERVER_NAME"]) &&
                        ($url_parts['host'] != $_SERVER["HTTP_HOST"])) {
                            $msg  = xarML('External URLs such as #(1) are not permitted as your home page.', $home);
                            $object->properties['userhome']->invalid .= $msg;
                            $isvalid = false;
                        }
                    }
                }
            }
            // instead of returning here, if the data is valid,
            // we could fall through to the updateitem phase

            // return the bool result to the calling function
            return $isvalid == true ? true : false;
        break;

        /**
         * The updateitem phase allows you to update user settings. Use this
         * when a standard $object->updateItem() call just isn't enough.
        **/
        case 'updateitem':
            // if you added the module name when you generated the authkey,
            // be sure to use it here when confirming :)
            if (!xarSecConfirmAuthKey('roles')) return;
            // data is already validated, go ahead and update the item
            $object->updateItem();
            // you could just return directly from here...
            /*
            // be sure to check for a returnurl
            if (!xarVarFetch('returnurl', 'pre:trim:str:1', $returnurl, '', XARVAR_NOT_REQUIRED)) return;
            // the default returnurl should be roles user account with a moduleload of current module
            if (empty($returnurl))
                $returnurl = xarModURL('roles', 'user', 'account', array('moduleload' => 'roles'));
            return xarController::redirect($returnurl);
            */
            // let the calling function know the update was a success
            return true;
        break;
    }
    // if we got here, we don't know what went wrong, just return false
    return false;
}

?>
