<?php
/**
 * Base Action Controller class
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

/**
 * Supported URLs :
 *
 * /base/
 * /base/[pagenâme]
 *
**/

sys::import('xaraya.mapper.controllers.short');

class BaseShortController extends ShortActionController
{
    function decode(Array $data=array())
    {
        $token = $this->firstToken();
        switch ($token) {
            case 'admin':
                return parent::decode($data);
            break;

            default:
                $data['func'] = 'main';
                $data['page'] = $token;
            break;
        }
        return $data;
    }
    
    public function encode(xarRequest $request)
    {  
        if ($request->getType() == 'admin') return parent::encode($request);

        $params = $request->getFunctionArgs();
        $path = array();
        switch($request->getFunction()) {
            case 'main':
                // Note : if your main function calls some other function by default,
                // you should set the path to directly to that other function
                $path[] = '';
                break;
            default:
                $path[] = '';
                break;
        }
        
        // Encode the processed params
        $request->setFunction($this->getFunction($path));
        
        // Send the unprocessed params back
        $request->setFunctionArgs($params);
        return parent::encode($request);
    }    
}
?>