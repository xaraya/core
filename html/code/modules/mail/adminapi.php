<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the modules admin API
 *
 * @method mixed internal_queuemail(array $args = [])
 * @method mixed internal_sendmail(array $args = [])
 * @method mixed internal_sendmail_new(array $args = [])
 * @method mixed createq(array $args = [])
 * @method mixed getmenulinks(array $args = []) Utility function pass individual menu items to the admin menu.
 * @method mixed getmessagestrings(array $args = [])
 * @method mixed getmessagetemplates(array $args = [])
 * @method mixed getqdef(array $args = [])
 * @method mixed getsourcefilename(array $args = []) Psspl : Added API function to determine the template sourcefile to use
 * @method mixed getsourcestring(array $args = []) Psspl : Added API function to read the contents of template files (.xt) as plain text
 * @method mixed hookmailchange(array $args = [])
 * @method mixed hookmailcreate(array $args = []) This is a hook function that is called to send mail on creation of an item
 * @method mixed hookmaildelete(array $args = []) This is a hook function that is called to send mail on deletion of an item
 * @method mixed mailmessagemodule(array $args = []) Psspl : Added API function to get the template file output for the mail message.
 * @method mixed mailsubjectmodule(array $args = []) Psspl : Added API function to get the template file output for the mail subject.
 * @method mixed maptoqueue(array $args = [])
 * @method mixed replace(array $args = []) utility function utility function to replace %%calls%%
 * @method mixed sendhtmlmail(array $args = []) This is a utility function that is called to send html mail - from any module regardless if the admin has configured html mail
 * @method mixed sendmail(array $args = []) This is a utility function that is called to send mail - from any module
 * @method mixed updatemessagestrings(array $args = [])
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
