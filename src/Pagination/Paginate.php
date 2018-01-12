<?php 
/**
 * 
 * This class is part of the core of Niuware WebFramework 
 * and it is not particularly intended to be modified.
 * For information about the license please visit the 
 * GIT repository at:
 * 
 * https://github.com/niuware/web-framework
 */

namespace Niuware\WebFramework\Pagination;

use Niuware\WebFramework\Http\Request;
use Illuminate\Database\Eloquent\Collection;

/**
 * Paginates data collections into chunks
 */
final class Paginate
{
    /**
     * The pagination attributes
     * 
     * @var array 
     */
    private $attributes = [];
    
    /**
     * The pagination data
     * 
     * @var \Illuminate\Database\Eloquent\Collection 
     */
    private $data;
    
    /**
     * Initializes the default pagination attributes
     * 
     * @return void
     */
    public function __construct()
    {
        $this->itemsPerPage = 15;
        $this->currentPage = 1;
        $this->previousPage = 1;
        $this->nextPage = 1;
        $this->url = '';
        $this->totalPages = 1;
        $this->renderedItemsMax = 8;
    }
    
    /**
     * Gets a pagination attribute
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->attributes[$name];
    }
    
    /**
     * Sets a pagination attribute
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * Sets the pagination base URL
     * 
     * @param string $sourceUrl
     * @return void
     */
    private function setUrl($sourceUrl)
    {
        $rawUrl = parse_url($sourceUrl);
        $rawQuery = (isset($rawUrl['query'])) ? $rawUrl['query'] : "";

        $url = str_replace($rawQuery, '', $sourceUrl);

        $query = preg_replace('/([\?|&]{0,1}p=\d*)/i', '', $rawQuery);

        $hasQuery = false;

        if (substr($url, -2) !== "/?") {
            
            $lastChar = substr($url, -1);

            if ($lastChar !== '/') {

                if ($lastChar === '?') {
                    
                    $url = substr($url, 0, -1) . "/?";
                }
                else {
                    
                    $url.= '/';
                }
            }
        }
        else {
            $hasQuery = true;
        }

        $this->url = $url . $query;

        if ($query === '') { 

            if ($hasQuery === false) {

                $this->url.= '?';
            }
        }
        else {

            $this->url.= '&';
        }

        $this->url.= 'p=';
    }
    
    /**
     * Generates the pagination
     * 
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param \Niuware\WebFramework\Http\Request $request
     */
    private function init($data, $request)
    {
        $this->totalPages = $data->chunk($this->itemsPerPage)->count();

        if ($this->totalPages > 1) {
            
            $page = $request->p;
            
            $this->currentPage = (isset($page)) ? $page : 1;
            
            if ($this->currentPage > $this->totalPages) {
                
                $this->currentPage = 1;
            }

            $this->previousPage = $this->currentPage - 1;
            
            $this->setUrl($request->headers()['Request-Uri']);
            
            if ($this->previousPage <= 0) {
                
                $this->previousPage = 1;
            }
            
            $this->nextPage = $this->currentPage + 1;
            
            if ($this->nextPage > $this->totalPages) {
                
                $this->nextPage = $this->totalPages;
            }
        }

        $this->data = $data->forPage($this->currentPage, $this->itemsPerPage);
    }
    
    /**
     * Renders the HTML code for a pagination item
     * 
     * @param int $i
     * @param string $itemClass
     * @param string $activeClass
     * @param string $linkClass
     * @return string
     */
    private function renderItem($i, $itemClass, $activeClass, $linkClass)
    {
        $html = '<li class="' . $itemClass . ' ';
        $html.= ($i == $this->currentPage) ? $activeClass : '';
        $html.= '">';
        $html.= '<a class="' . $linkClass . '" href="';
        $html.= $this->url . $i;
        $html.= '">' . $i . '</a></li>';
        
        return $html;
    }
    
    /**
     * Renders the HTML code for a pagination ellipsis item
     * 
     * @param string $itemClass
     * @param string $linkClass
     * @return string
     */
    private function renderEllipsisItem($itemClass, $linkClass)
    {
        $html = '<li class="' . $itemClass . '">';
        $html.= '<a class="' . $linkClass . '"';
        $html.= '">...</a></li>';
        
        return $html;
    }
    
    /**
     * Renders the HTML code for a pagination with clipping mode
     * 
     * @param string $itemClass
     * @param string $activeClass
     * @param string $linkClass
     * @return string
     */
    private function renderWithClipping($itemClass, $activeClass, $linkClass)
    {
        $start = 1;
        $end = $this->totalPages;
        $itemsToRender = $this->renderedItemsMax;

        $this->calculateClippingIndexes($start, $end, $itemsToRender);

        $html = $this->renderItem(1, $itemClass, $activeClass, $linkClass);

        if ($this->currentPage > $itemsToRender) {

            if (($start - 1) == 2) {
                
                $html.= $this->renderItem(2, $itemClass, $activeClass, $linkClass);
            }
            else {
                
                if (($start + 1) > 2) {
                    
                    $html.= $this->renderEllipsisItem($itemClass, $linkClass);
                }
            }
        }
        
        if ($start === 1) {
            
            $start+= 1;
        }

        for ($i = $start; $i <= $end; $i++) {
            
            $html.= $this->renderItem($i, $itemClass, $activeClass, $linkClass);
        }

        if ($end < $this->totalPages) {

            if (($end + 1) != $this->totalPages) {
                
                $html.= $this->renderEllipsisItem($itemClass, $linkClass);
            }
            
            $html.= $this->renderItem($this->totalPages, $itemClass, $activeClass, $linkClass);
        }
        
        return $html;
    }
    
    /**
     * Calculate the indexes for rendering the pagination HTML code
     * 
     * @param int $start
     * @param int $end
     * @param int $itemsToRender
     * @return void
     */
    private function calculateClippingIndexes(&$start, &$end, &$itemsToRender)
    {
        $startOffset = 3;
        $itemsToRender = $this->renderedItemsMax - $startOffset;
        $startTemp = ($this->currentPage == 1) ? 2 : $this->currentPage;
        $end = (($this->currentPage + $itemsToRender) > $this->totalPages) ? $this->totalPages : ($this->currentPage + $itemsToRender);

        if (($end - $startTemp) < $itemsToRender) {

            $startTemp-= ($itemsToRender - ($end - $startTemp));
        }

        $startTemp1 = (($startTemp - $startOffset) < 1) ? 2 : ($startTemp - $startOffset);
        $start = ($startTemp1 > $this->totalPages) ? $startTemp1 + $startOffset : $startTemp1;

        if (($end - $start) < $this->renderedItemsMax) {

            $end+= ($this->renderedItemsMax - ($end - $start)) - 1;
        }
    }
    
    /**
     * Paginates the data
     * 
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param \Niuware\WebFramework\Http\Request $request
     * @return void
     */
    public function paginate(Collection $data, Request $request)
    {
        $this->init($data, $request);
    }
    
    /**
     * Returns the chunk of data for the current Request
     * 
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function data()
    {
        return $this->data; 
    }
    
    /**
     * Returns the string for the 'previous link'
     * 
     * @param string $label
     * @param string $itemClass
     * @param string $linkClass
     * @return string
     */
    public function previousLink($label = '&lt;', $itemClass = 'page-item', $linkClass = 'page-link')
    {
        $html = '<li class="' . $itemClass . '">';
        $html.= '<a class="' . $linkClass . '" href="';
        $html.= $this->url . $this->previousPage . '"';
        $html.= ($this->currentPage == 1) ? ' style="cursor:default;"' : '';
        $html.= '>';
        $html.= $label;
        $html.= '</a>'; 
        $html.= '</li>';
        
        return $html;
    }
    
    /**
     * Returns the string for the 'next link'
     * 
     * @param string $label
     * @param string $itemClass
     * @param string $linkClass
     * @return string
     */
    public function nextLink($label = '&gt;', $itemClass = 'page-item', $linkClass = 'page-link')
    {
        $html = '<li class="' . $itemClass . '">';
        $html.= '<a class="' . $linkClass . '" href="';
        $html.= $this->url . $this->nextPage . '"';
        $html.= ($this->currentPage == $this->totalPages) ? ' style="cursor:default;"' : '';
        $html.= '>';
        $html.= $label;
        $html.= '</a>'; 
        $html.= '</li>';
        
        return $html;
    }
        
    /**
     * Renders the HTML code for the pagination
     * 
     * @param string $previousLabel
     * @param string $nextLabel
     * @param string $itemClass
     * @param string $linkClass
     * @param string $activeClass
     * @return void
     */
    public function render($previousLabel = '&lt;', $nextLabel = '&gt;', 
            $itemClass = 'page-item', $linkClass = 'page-link', $activeClass = 'active')
    {
        
        $html = $this->previousLink($previousLabel, $itemClass, $linkClass, $activeClass);
        
        $useClipping = false;
        
        if ($this->totalPages > $this->renderedItemsMax) {
            
            $useClipping = true;
        }
        
        if ($useClipping === false) {
        
            for ($i = 1; $i <= $this->totalPages; $i++) {
                
                $html.= $this->renderItem($i, $itemClass, $activeClass, $linkClass);
            }
        }
        else {
            
            $html.= $this->renderWithClipping($itemClass, $activeClass, $linkClass);
        }
        
        $html.= $this->nextLink($nextLabel, $itemClass, $linkClass, $activeClass);
        
        echo $html;
    }
    
    /**
     * Verifies the necessity to render the pagination HTML
     * 
     * @return boolean
     */
    public function shouldRender()
    {
        if ($this->totalPages > 1) {
            
            return true;
        }
        
        return false;
    }
}
