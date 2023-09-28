<?php
/**
 * Short Action Controller class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.controllers.base');
sys::import('xaraya.mapper.controllers.interfaces');

class ShortActionController extends BaseActionController implements iController
{
    public static string $delimiter = '?';    // This character divides the URL into initial path and parameters
    public string $separator = '/';

    /**
     * Summary of decode
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function decode(array $data = []): array
    {
        $token = $this->firstToken();
        if (xarController::$request->getModule() == 'object') {
            $data['type'] = $token;
            if ($token == 'admin') {
                // No admin equivalent for objectURL for now
                xarController::$request->setModule('dynamicdata');
                $token = false;
            }
            $token = $this->nextToken();
            $data['func'] = empty($token) ? xarController::$method : $token;
        } else {
            if ($token == 'admin') {
                $data['type'] = $token;
                $token = $this->nextToken();
            }
            // If no function was passed we get the default
            $data['func'] = empty($token) ? xarController::$func : $token;
        }
        return $data;
    }

    public function encode(xarRequest $request): string
    {
        $path = $this->getInitialPath($request);
        $path .= self::$delimiter;
        foreach ($request->getFunctionArgs() as  $key => $value) {
            $path .= $key . '=' . $value . xarController::$separator;
        }
        $path = substr($path, 0, strlen($path) - 1);
        return $this->separator . $path;
    }

    public function getActionString(xarRequest $request): string
    {
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $actionstring = substr($request->getURL(), strlen($initialpath) + 1);
        $delimiterposition = strpos($actionstring, xarController::$delimiter);
        if ($delimiterposition) {
            $actionstring = substr($actionstring, 0, $delimiterposition);
        }
        $separatorposition = strpos($actionstring, $this->separator);
        if (false === $separatorposition) {
            return "";
        }
        $actionstring = substr($actionstring, $separatorposition + 1);
        return $actionstring;
    }

    public function getInitialPath(xarRequest $request): string
    {
        $path = $request->getModule();
        if ('user' != $request->getType()) {
            $path .= $this->separator . $request->getType();
        }
        $path .= $this->separator . $request->getFunction();
        return $path;
    }

    /**
     * Summary of getFunction
     * @param array<string> $params
     * @return string
     */
    public function getFunction(array $params)
    {
        return implode($this->separator, $params);
    }
}
