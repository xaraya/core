<?php
/**
 * Base Action Controller class
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
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
                if (!empty($params['page'])) {                    
                    $path[] = $params['page'];
                    unset($params['page']);
                } else { 
                    $path[] = '';
                }
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