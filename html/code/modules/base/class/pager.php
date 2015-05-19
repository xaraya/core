<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

class xarTplPager Extends Object
{
    /**
     * Creates pager information with no assumptions to output format.
     *
     * @since 2003/10/09
     * 
     * @param int $currentItem Start item
     * @param int $total Total number of items present
     * @param int $itemsPerPage Number of links to dispsplay (default 10)
     * @param int|array $blockOptions Number of pages to display at once (default 10) or array of optinal parameters
     * @return array Data array containing pager info
     */
    public static function getInfo($currentItem, $total, $itemsPerPage = 10, $blockOptions = 10)
    {
        /**
         * Pending
         * 
         * @todo  Move this somewhere else, preferably transparent and a widget (which might be mutually exclusive)
         */
        // Default block options.
        if (is_numeric($blockOptions)) {
            $pageBlockSize = $blockOptions;
        }

        if (is_array($blockOptions)) {
            if (!empty($blockOptions['blocksize'])) {$blockSize = $blockOptions['blocksize'];}
            if (!empty($blockOptions['firstitem'])) {$firstItem = $blockOptions['firstitem'];}
            if (!empty($blockOptions['firstpage'])) {$firstPage = $blockOptions['firstpage'];}
            if (!empty($blockOptions['urltemplate'])) {$urltemplate = $blockOptions['urltemplate'];}
            if (!empty($blockOptions['urlitemmatch'])) {
                $urlItemMatch = $blockOptions['urlitemmatch'];
            } else {
                $urlItemMatch = '%%';
            }
            $urlItemMatchEnc = rawurlencode($urlItemMatch);
        }

        // Default values.
        if (empty($blockSize) || $blockSize < 1) {$blockSize = 10;}
        if (empty($firstItem)) {$firstItem = 1;}
        if (empty($firstPage)) {$firstPage = 1;}

        // The last item may be offset if the first item is not 1.
        $lastItem = ($total + $firstItem - 1);

        // Sanity check on arguments.
        if ($itemsPerPage < 1) {$itemsPerPage = 10;}
        if ($currentItem < $firstItem) {$currentItem = $firstItem;}
        if ($currentItem > $lastItem) {$currentItem = $lastItem;}

        // If this request was the same as the last one, then return the cached pager details.
        // TODO: is there a better way of caching for each unique request?
        $request = md5($currentItem . ':' . $lastItem . ':' . $itemsPerPage . ':' . serialize($blockOptions));
        if (xarCore::getCached('Pager.core', 'request') == $request) {
            return xarCore::getCached('Pager.core', 'details');
        }

        // Record the values in this request.
        xarCore::setCached('Pager.core', 'request', $request);

        // Max number of items in a block of pages.
        $itemsPerBlock = ($blockSize * $itemsPerPage);

        // Get the start and end items of the page block containing the current item.
        $blockFirstItem = $currentItem - (($currentItem - $firstItem) % $itemsPerBlock);
        $blockLastItem = $blockFirstItem + $itemsPerBlock - 1;
        if ($blockLastItem > $lastItem) {$blockLastItem = $lastItem;}

        // Current/Last page numbers.
        $currentPage = (int)ceil(($currentItem-$firstItem+1) / $itemsPerPage) + $firstPage - 1;
        $totalPages = (int)ceil($total / $itemsPerPage);

        // First/Current/Last block numbers
        $firstBlock = 1;
        $currentBlock = (int)ceil(($currentItem-$firstItem+1) / $itemsPerBlock);
        $totalBlocks = (int)ceil($total / $itemsPerBlock);

        // Get start and end items of the current page.
        $pageFirstItem = $currentItem - (($currentItem-$firstItem) % $itemsPerPage);
        $pageLastItem = $pageFirstItem + $itemsPerPage - 1;
        if ($pageLastItem > $lastItem) {$pageLastItem = $lastItem;}

        // Initialise data array.
        $data = array();

        $data['middleitems'] = array();
        $data['middleurls'] = array();
        $pageNum = (int)ceil(($blockFirstItem - $firstItem + 1) / $itemsPerPage) + $firstPage - 1;
        for ($i = $blockFirstItem; $i <= $blockLastItem; $i += $itemsPerPage) {
            if (!empty($urltemplate)) {
                $data['middleurls'][$pageNum] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $i, $urltemplate);
            }
            $data['middleitems'][$pageNum] = $i;
            $data['middleitemsfrom'][$pageNum] = $i;
            $data['middleitemsto'][$pageNum] = $i + $itemsPerPage - 1;
            if ($data['middleitemsto'][$pageNum] > $total) {$data['middleitemsto'][$pageNum] = $total;}
            $pageNum += 1;
        }

        $data['currentitem'] = $currentItem;
        $data['totalitems'] = $total;
        $data['lastitem'] = $lastItem;
        $data['firstitem'] = $firstItem;
        $data['itemsperpage'] = $itemsPerPage;
        $data['itemsperblock'] = $itemsPerBlock;
        $data['pagesperblock'] = $blockSize;

        $data['currentblock'] = $currentBlock;
        $data['totalblocks'] = $totalBlocks;
        $data['firstblock'] = $firstBlock;
        $data['lastblock'] = $totalBlocks;
        $data['blockfirstitem'] = $blockFirstItem;
        $data['blocklastitem'] = $blockLastItem;

        $data['currentpage'] = $currentPage;
        $data['currentpagenum'] = $currentPage;
        $data['totalpages'] = $totalPages;
        $data['pagefirstitem'] = $pageFirstItem;
        $data['pagelastitem'] = $pageLastItem;

        // These two are item numbers. The naming is historical.
        $data['firstpage'] = $firstItem;
        $data['lastpage'] = $lastItem - (($lastItem-$firstItem) % $itemsPerPage);

        if (!empty($urltemplate)) {
            // These two links are for first and last pages.
            $data['firsturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['firstpage'], $urltemplate);
            $data['lasturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['lastpage'], $urltemplate);
        }

        $data['firstpagenum'] = $firstPage;
        $data['lastpagenum'] = ($totalPages + $firstPage - 1);

        // Data for previous page of items.
        if ($currentPage > $firstPage) {
            $data['prevpageitems'] = $itemsPerPage;
            $data['prevpage'] = ($pageFirstItem - $itemsPerPage);
            if (!empty($urltemplate)) {
                $data['prevpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevpage'], $urltemplate);
            }
        } else {
            $data['prevpageitems'] = 0;
        }

        // Data for next page of items.
        if ($pageLastItem < $lastItem) {
            $nextPageLastItem = ($pageLastItem + $itemsPerPage);
            if ($nextPageLastItem > $lastItem) {$nextPageLastItem = $lastItem;}
            $data['nextpageitems'] = ($nextPageLastItem - $pageLastItem);
            $data['nextpage'] = ($pageLastItem + 1);
            if (!empty($urltemplate)) {
                $data['nextpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextpage'], $urltemplate);
            }
        } else {
            $data['nextpageitems'] = 0;
        }

        // Data for previous block of pages.
        if ($currentBlock > $firstBlock) {
            $data['prevblockpages'] = $blockSize;
            $data['prevblock'] = ($blockFirstItem - $itemsPerBlock);
            if (!empty($urltemplate)) {
                $data['prevblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevblock'], $urltemplate);
            }
        } else {
            $data['prevblockpages'] = 0;
        }

        // Data for next block of pages.
        if ($currentBlock < $totalBlocks) {
            $nextBlockLastItem = ($blockLastItem + $itemsPerBlock);
            if ($nextBlockLastItem > $lastItem) {$nextBlockLastItem = $lastItem;}
            $data['nextblockpages'] = ceil(($nextBlockLastItem - $blockLastItem) / $itemsPerPage);
            $data['nextblock'] = ($blockLastItem + 1);
            if (!empty($urltemplate)) {
                $data['nextblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextblock'], $urltemplate);
            }
        } else {
            $data['nextblockpages'] = 0;
        }

        // Cache all the pager details.
        xarCore::setCached('Pager.core', 'details', $data);

        return $data;

    }

    /**
     * Equivalent of pnHTML()'s Pager function (used by the base-pager template tag widget)
     *
     * @since 1.13 - 2003/10/09
     * 
     * @param integer $startnum     start item
     * @param integer $total        total number of items present
     * @param string  $urltemplate  template for url, will replace '%%' with item number
     * @param integer $perpage      number of links to display (default=10)
     * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
     * @param integer $template     alternative template name within $tplmodule/user (default 'pager')
     * @param string  $tplmodule    alternative module to look for templates in (default 'base')
     * @return string output display string
     *
     */
    public static function getPager($startNum, $total, $urltemplate, $itemsPerPage = 10, $blockOptions = array(), $template = 'default', $tplmodule = 'base')
    {
        // Quick check to ensure that we have work to do
        if ($total <= $itemsPerPage) {return '';}

        // Number of pages in a page block - support older numeric 'pages per block'.
        if (is_numeric($blockOptions)) {
            $blockOptions = array('blocksize' => $blockOptions);
        }

        // Pass the url template into the pager calculator.
        $blockOptions['urltemplate'] = $urltemplate;

        // Get the pager information.
        $data = self::getInfo($startNum, $total, $itemsPerPage, $blockOptions);

        // Nothing to do: perhaps there is an error in the parameters?
        if (empty($data)) {return '';}

        // Couple of cached values used in various pages.
        // It is unclear what these values are supposed to be used for.
        if ($data['prevblockpages'] > 0) {
            xarCore::setCached('Pager.first', 'leftarrow', $data['firsturl']);
        }

        // Links for next block of pages.
        if ($data['nextblockpages'] > 0) {
            xarCore::setCached('Pager.last', 'rightarrow', $data['lasturl']);
        }

        return trim(xarTpl::module($tplmodule, 'pager', $template, $data));
    }

    /**
     * Return formatted pagerurl for user in getPager method
     * 
     * @param type $urlitemmatch
     * @param type $urltemplate
     * @return string
     */
    public static function getPagerURL($urlitemmatch = '%%', $urltemplate = null)
    {
        if (empty($urltemplate))
            return xarServer::getCurrentURL(array('startnum' => $urlitemmatch));
	    $rawurlitemmatch = $urlitemmatch;
        $urlitemmatch = rawurlencode($urlitemmatch);

        if (strpos($urltemplate, $urlitemmatch) === false) {
            if (preg_match('/startnum=(.*)?(&amp;|$)/', $urltemplate)) {
                $urltemplate = preg_replace("/startnum=$rawurlitemmatch/", 'startnum='.$urlitemmatch.'\\2', $urltemplate);
            } else {
                $urljoin = preg_match('/\?/', $urltemplate) ? '&amp;' : '?';
                $urltemplate .= $urljoin . 'startnum=' . $urlitemmatch;
            }
        }
        return $urltemplate;
    }
}
?>