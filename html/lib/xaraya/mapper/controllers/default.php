<?php

/**
 * Default Action Controller class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.controllers.base');
sys::import('xaraya.mapper.controllers.interfaces');
sys::import('xaraya.requests.url');
use Xaraya\Requests\RequestURL;
use Xaraya\Services\xar;

class DefaultActionController extends BaseActionController implements iController
{
    public string $separator = '&';

    /**
     * Summary of decode
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function decode(array $data = []): array
    {
        $request = $this->getRequest();
        $xar = xar::getServicesClass();
        // @todo avoid duplication of param parsing - see xarRequest::setURL()
        $xar->var()->find('module', $module, 'regexp:/^[a-z][a-z_0-9]*$/');
        if (null != $module) {
            $xar->var()->find('type', $data['type'], "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $request->getType());
            $xar->var()->find('func', $data['func'], "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $request->getFunction());
        }
        $xar->var()->find('object', $object, 'regexp:/^[a-z][a-z_0-9]*$/');
        if (null != $object) {
            $data['object'] = $object;
            $xar->var()->find('method', $data['method'], "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $request->getMethod());

            // No admin equivalent for objectURL for now
            if (($request->getModule() == 'object') && $request->getType() == 'admin') {
                $request->setModule('dynamicdata');
                $data['func'] = 'view';
            }
        }
        return $data;
    }

    public function encode(xarRequest $request): string
    {
        if ($request->getModule() == 'object') {
            $pathargs['object'] = $request->getType();
            $pathargs['method'] = $request->getFunction();
        } else {
            $pathargs[$request->getModuleKey()] = $request->getModule();
            $pathargs[$request->getTypeKey()] = $request->getType();
            $pathargs[$request->getFunctionKey()] = $request->getFunction();
        }
        $pathargs = $pathargs + $request->getFunctionArgs();
        $path = RequestURL::addParametersToPath($pathargs, '', xarController::$delimiter, $this->separator);
        return $path;
    }

    public function getActionString(xarRequest $request): string
    {
        $initialpath = $request->getBaseURL() . $request->getEntryPoint();
        if (str_starts_with($request->getURL(), $initialpath)) {
            $actionstring = substr($request->getURL() ?? '', strlen($initialpath));
        } else {
            $actionstring = '';
        }
        return $actionstring;
    }

    public function getInitialPath(xarRequest $request): string
    {
        return '';
    }
}
