<?php
/**
 * DynamicData Default Action Controller class
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
**/

/**
 * Supported URLs :
 *
 * @todo
**/

sys::import('xaraya.mapper.controllers.default');

/**
 * DynamicData default controller - classname is currently fixed in xarDispatcher::findController()
 */
class DynamicdataDefaultController extends DefaultActionController
{
    /**
     * Summary of decode
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function decode(array $data = []): array
    {
        return parent::decode($data);
    }

    public function encode(xarRequest $request): string
    {
        return parent::encode($request);
    }

    public function getActionString(xarRequest $request): string
    {
        return parent::getActionString($request);
    }

    public function getInitialPath(xarRequest $request): string
    {
        return parent::getInitialPath($request);
    }

    /**
     * Summary of chargeRequest
     * @param xarRequest $request
     * @param array<string, mixed> $params
     * @return void
     */
    public function chargeRequest(xarRequest $request, array $params = []): void
    {
        // @todo Deal with object-specific parameters here someday so that the base controller in core doesn't have to
        //if (isset($params['object'])) {
        //    $request->setType($params['object']);
        //    unset($params['object']);
        //}
        //if (isset($params['method'])) {
        //    $request->setFunction($params['method']);
        //    unset($params['method']);
        //}
        parent::chargeRequest($request, $params);
    }

    public function run(xarRequest $request = null, xarResponse $response = null): void
    {
        // Now get the output - @todo we'll never get here atm when xarDispatcher::findController() is looking for the 'object' module :-)
        //if ($request->getModule() == 'object') {
        //    sys::import('xaraya.objects');
        //    $response->output = xarDDObject::guiMethod($request->getType(), $request->getFunction(), $request->getFunctionArgs());
        //}
        parent::run($request, $response);
    }
}
