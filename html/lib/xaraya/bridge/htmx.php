<?php
/**
 * Experiment with htmx - @todo
 */

namespace Xaraya\Bridge\Routing;

/**
 * Htmx handler
 */
class HtmxHandler
{
    /** @var string */
    protected $prefix;
    /** @var string */
    protected $target;
    /** @var string */
    protected $script;
    /** @var FastRouteBridge */
    protected $bridge;

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
        $this->target = 'main-module-output';
        $this->script = '<script src="https://unpkg.com/htmx.org@2.0.2" integrity="sha384-Y7hw+L/jvKeWIRRkqWYfPcvVxHzVzn5REgzbawhxAuQGwX1XWe70vji+VSeHOThJ" crossorigin="anonymous"></script>';
        $this->bridge = new FastRouteBridge();
    }

    /**
     * Summary of run
     * @param mixed $request
     * @return void
     */
    public function run(&$request = null)
    {
        $method = $this->bridge::getMethod($request);
        $path = $this->bridge::getPathInfo($request);
        $server = $this->bridge::getServerParams($request);
        [$result, $context] = $this->bridge->dispatchRequest($method, $path, $this->prefix, $request);
        if (!empty($server['HTTP_HX_REQUEST']) &&
            empty($server['HTTP_HX_HISTORY_RESTORE_REQUEST']) &&
            !empty($server['HTTP_HX_TARGET']) && $server['HTTP_HX_TARGET'] == $this->target) {
            $this->bridge->wrapPage = false;
            // @todo use inheritance for other htmx attributes?
            $transform = function ($result) {
                //return str_replace(' href="/', ' href="#" hx-push-url="true" hx-target="#' . $this->target . '" hx-get="/', $result);
                return $result;
            };
        } else {
            $this->bridge->wrapPage = true;
            $transform = function ($result) {
                //return str_replace('</head>', $script . '</head>', str_replace(' href="/', ' href="#" hx-push-url="true" hx-target="#' . $this->target . '" hx-get="/', $result));
                return str_replace('</head>', $this->script . '</head>', str_replace('<body', '<body hx-boost="true" hx-target="#' . $this->target . '"', $result));
            };
        }
        $this->bridge->output($result, $context, $transform);
    }
}
