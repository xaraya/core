<?php
/**
 * Authsystem Action Controller class
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

/**
 * Supported URLs :
 *
 * /authsystem/
 * /authsystem/login
 * /authsystem/logout
 * /authsystem/password
 *
**/

sys::import('xaraya.mapper.controllers.short');

/**
 * Authsystem Short Contrller implementation
 */
class AuthsystemShortController extends ShortActionController
{
    /**
     * Function to decode data
     *
     * @param array<string, mixed> $data Data array to be decoded.
     * @return array<string, mixed> Returns decoded data array.
     */
    public function decode(array $data = []): array
    {
        $token = $this->firstToken();
        switch ($token) {
            case 'admin':
                return parent::decode($data);

            case 'login':
                $data['func'] = 'showloginform';
                break;

            case 'auth':
                $data['func'] = 'login';
                break;

            case 'logout':
                $data['func'] = 'logout';
                break;

            case 'password':
                $data['func'] = 'password';
                break;

            default:
                $data['func'] = 'showloginform';
                break;
        }
        return $data;
    }

    /**
     * Method to encode xarRequest object
     *
     * @param xarRequest $request Request object to be encoded
     * @return string Returns encoded request string
     */
    public function encode(xarRequest $request): string
    {
        if ($request->getType() == 'admin') {
            return parent::encode($request);
        }

        $params = $request->getFunctionArgs();
        $path = [];
        switch($request->getFunction()) {
            case 'main':
                // Note : if your main function calls some other function by default,
                // you should set the path to directly to that other function
                $path[] = '';
                break;
            case 'showloginform':
                $path[] = 'login';
                break;
            case 'login':
                $path[] = 'auth';
                break;
            default:
                $path[] = $request->getFunction();
                break;
        }

        // Encode the processed params
        $request->setFunction($this->getFunction($path));

        // Send the unprocessed params back
        $request->setFunctionArgs($params);
        return parent::encode($request);
    }
}
