<?php //-->
/**
 * This file is part of the Handlebars PHP Template Engine.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

namespace HandlebarsPHP;

/**
 * Welcome to Handlebars!
 *
 * This definition wraps the Engine to match
 * the handlebars API as close as possible
 *
 * @package  Handlebars
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @standard PSR-2
 */
class Handler
{
    /**
     * @const string FILE_PREFIX
     */
    const FILE_PREFIX = '__HANDLEBARS__';

    /**
     * @var string $prefix You can change the file prefix with setPrefix()
     */
    protected $prefix = self::FILE_PREFIX;

    /**
     * @var string|null $cache The cache path location
     */
    protected $cache = null;

    /**
     * @var array $callbacks A list of compiled template callbacks
     */
    protected static $callbacks = [];

    /**
     * Just load the default helpers
     */
    public function __construct()
    {
        $helpers = include __DIR__.'/helpers.php';

        foreach ($helpers as $name => $helper) {
            $this->registerHelper($name, $helper);
        }
    }

    /**
     * Returns a callback that binds the data with the template
     *
     * @param *string $template the template string
     *
     * @return function The template binding handler
     */
    public function compile($template)
    {
        $name = md5($template);

        if (isset(self::$callbacks[$name])) {
            return self::$callbacks[$name];
        }

        $file = $this->cache . '/' . $this->prefix . $name . '.php';

        if (is_dir($this->cache) && file_exists($file)) {
            $callback = include($file);
        } else {
            $compiler = new Compiler($this, $template);
            $code = $compiler->compile();

            if (is_dir($this->cache)) {
                file_put_contents($file, $code);
            }

            //called like: function($data) {};
            $callback = @eval('?>'.$code);
            //$this->checkEval($code);
        }

        self::$callbacks[$name] = $callback;

        return $callback;
    }

    /**
     * Returns the cache location
     *
     * @return string|null
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Returns a specific helper
     *
     * @param *string $name The name of the helper
     *
     * @return function|null
     */
    public function getHelper($name)
    {
        return Runtime::getHelper($name);
    }

    /**
     * Returns all the registered helpers
     *
     * @return array
     */
    public function getHelpers()
    {
        return Runtime::getHelpers();
    }

    /**
     * Returns a specific partial
     *
     * @param *string $name The name of the helper
     *
     * @return string|null
     */
    public function getPartial($name)
    {
        return Runtime::getPartial($name);
    }

    /**
     * Returns all the registered partials
     *
     * @return array
     */
    public function getPartials()
    {
        return Runtime::getPartials();
    }

    /**
     * The famous register helper matching the Handlebars API
     *
     * @param *string   $name   The name of the helper
     * @param *callable $helper The helper handler
     *
     * @return Handler
     */
    public function registerHelper($name, $helper)
    {
        Runtime::registerHelper($name, $helper);
        return $this;
    }

    /**
     * Delays registering partials to the engine
     * because there is no add partial method...
     *
     * @param *string $name    The name of the helper
     * @param *string $partial The helper handler
     *
     * @return Handler
     */
    public function registerPartial($name, $partial)
    {
        Runtime::registerPartial($name, $partial);
        return $this;
    }

    /**
     * Resets the helpers and partials
     *
     * @return Handler
     */
    public function reset()
    {
        Runtime::flush();
        $this->__construct();
        return $this;
    }

    /**
     * Enables the cache option
     *
     * @param *string The cache path
     *
     * @return Handler
     */
    public function setCache($path)
    {
        $this->cache = $path;

        return $this;
    }

    /**
     * Sets the file name prefix
     *
     * @param *string $prefix
     *
     * @return Handler
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * The opposite of registerHelper
     *
     * @param *string $name the helper name
     *
     * @return Handler
     */
    public function unregisterHelper($name)
    {
        Runtime::unregisterHelper($name);
        return $this;
    }

    /**
     * The opposite of registerPartial
     *
     * @param *string $name the partial name
     *
     * @return Handler
     */
    public function unregisterPartial($name)
    {
        Runtime::unregisterPartial($name);
        return $this;
    }

    /**
     * Returns a very nice error message
     *
     * @param *string $code
     *
     * @return Handler
     */
    protected function checkEval($code)
    {
        $error = error_get_last();

        if (isset($error['message'])
            && isset($error['line'])
            && $error['message'] === 'parse error'
        ) {
            $code = explode("\n", $code);
            $start = $error['line'] - 25;
            if ($start < 0) {
                $start = 0;
            }

            $code = array_splice($code, $start, 50);

            foreach ($code as $i => $line) {
                $code[$i] = (++$start) . ': ' . $line;
            }

            throw Exception::forCompileError($error, $code);
        }

        return $this;
    }
}
