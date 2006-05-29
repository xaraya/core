<?php
/**
 * Render a core exception
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Renders a core exception and then exits
 *
 * @subpackage base
 * @author Marc Lutolf
 */
function base_user_systemexit()
{
    global $CoreStack;
    $errorcodes = array(
                '1' => "E_ERROR",
                '2' => "E_WARNING",
                '4' => "E_PARSE",
                '8' => "E_NOTICE",
                '16' => "E_CORE_ERROR",
                '32' => "E_CORE_WARNING",
                '64' => "E_COMPILE_ERROR",
                '128' => "E_COMPILE_WARNING",
                '256' => "E_USER_ERROR",
                '512' => "E_USER_WARNING",
                '1024' => "E_USER_NOTICE"
                );
    if (!xarVarFetch('exception', 'str', $msg, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('product', 'str', $product, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('component', 'str', $component, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('code', 'str', $code, NULL, XARVAR_NOT_REQUIRED)) return;
    if($CoreStack->isempty()) $CoreStack->initialize();
    // avoid nasties trying to post fake exceptions
    $msg = xarVarPrepHTMLDisplay($msg);
    $exception = new SystemException($msg);
    if (empty($code) || !isset($errorcodes[$code])) {
        $code = 1;
    }
    $exception->setID($errorcodes[$code]);
    $exception->setMajor(XAR_SYSTEM_EXCEPTION);
    if ($component != '') {
    // CHECKME: sanitize this too ?
        $exception->setProduct($product);
        $exception->setComponent($component);
    }
    $CoreStack->push($exception);

    static $spinning = false;

    if ($spinning) {
        echo "Hit a reoccurring error. Here is the original error message:";
        echo "<br /><br />" . $msg;
    }
    else {
        $spinning = true;
        $text = xarErrorRender('template', "CORE");
        $pageOutput = xarTpl_renderPage($text);
        echo $pageOutput;
    }
    exit;
}
?>
