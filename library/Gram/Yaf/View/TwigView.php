<?php
namespace Gram\Yaf\View;

use \Twig_Loader_Filesystem;
use \Twig_Environment;

class TwigView implements \Yaf\View_Interface
{
    /**
     * @var \Twig_Loader_Filesystem
     */
    protected $loader;
    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @param string $template_dir
     * @param array $options
     */
    function __construct($template_dir, array $options = [])
    {
        $this->loader = new Twig_Loader_Filesystem($template_dir);
        $this->twig = new Twig_Environment($this->loader, $options);
    }

    /**
     * @param string $name
     * @return bool
     */
    function __isset($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    function __get($name)
    {
        return $this->variables[$name];
    }

    /**
     * @param string $name
     */
    function __unset($name)
    {
        unset($this->variables[$name]);
    }

    /**
     * Return twig instance
     * @return \Twig_Environment
     */
    function twig()
    {
        return $this->twig;
    }

    /**
     * Assign values to View engine, then the value can access directly by name in template.
     *
     * @link http://www.php.net/manual/en/yaf-view-interface.assign.php
     *
     * @param string|array $name
     * @param mixed $value
     * @return bool
     */
    function assign($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Render a template and output the result immediately.
     *
     * @link http://www.php.net/manual/en/yaf-view-interface.display.php
     *
     * @param string $tpl
     * @param array $tpl_vars
     * @return bool
     */
    function display($tpl, array $tpl_vars = null)
    {
        echo $this->render($tpl, $tpl_vars);
    }

    /**
     * @link http://www.php.net/manual/en/yaf-view-interface.getscriptpath.php
     *
     * @return string
     */
    function getScriptPath()
    {
        $paths = $this->loader->getPaths();
        return reset($paths);
    }

    /**
     * Render a template and return the result.
     *
     * @link http://www.php.net/manual/en/yaf-view-interface.render.php
     *
     * @param string $tpl
     * @param array $tpl_vars
     * @return string
     */
    function render($tpl, array $tpl_vars = null)
    {
        if (is_array($tpl_vars)) {
            $this->variables = array_merge($this->variables, $tpl_vars);
        }
        return $this->twig->loadTemplate($tpl)->render($this->variables);
    }

    /**
     * Set the templates base directory, this is usually called by\Yaf\Dispatcher
     *
     * @link http://www.php.net/manual/en/yaf-view-interface.setscriptpath.php
     *
     * @param string $template_dir An absolute path to the template directory, by default,\Yaf\Dispatcher use application.directory . "/views" as this parameter.
     */
    function setScriptPath($template_dir)
    {
        $this->loader->setPaths($template_dir);
    }

}