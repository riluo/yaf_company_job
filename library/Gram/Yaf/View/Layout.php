<?php
namespace Gram\Yaf\View;

/**
 * 轻量的模板视图引擎
 *
 * Class Layout
 * @package Gram\Yaf\View
 */
class Layout
{
    static protected $definedVars = array();

    protected $variables = array();

    protected $layout;

    /**
     * @param string $layout 模板页绝对路径
     */
    function __construct($layout)
    {
        if (empty($layout)) {
            throw new \InvalidArgumentException('变量名不能为空');
        }

        $this->ensureLayoutExist($layout);
        $this->layout = $layout;
    }

    /**
     * 确保母板文件存在
     *
     * @param string $layout
     */
    protected function ensureLayoutExist($layout)
    {
        if (!file_exists($layout)) {
            throw new \InvalidArgumentException('不存在的模板页');
        }
    }

    /**
     * 设置母板页的变量值
     *
     * @param string $name
     * @param string $value
     */
    function variable($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * 标记变量内容的开始位置
     *
     * @param string $name
     */
    function beginVariable($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('变量名不能为空');
        }

        $this->variables[$name] = null;

        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * 标记变量内容的结束位置
     */
    function endVariable()
    {
        if (($name = array_pop(array_keys($this->variables))) !== null) {
            $this->variables[$name] = ob_get_clean();
        } else {
            ob_end_clean();
        }
    }

    /**
     * 渲染整体页面
     */
    function end()
    {
        if (!empty(self::$definedVars)) {
            $variables = array_merge(self::$definedVars, $this->variables);
        } else {
            $variables = $this->variables;
            self::$definedVars = $this->variables;
        }
        extract($variables, EXTR_PREFIX_SAME, 'data');
        require($this->layout);
    }

    /**
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        $this->variable($name, $value);
    }


    /**
     * 创建母板视图
     *
     * @param string $layout
     * @param string $view
     * @return Layout
     */
    static function master($layout, $view)
    {
        $layout = dirname($view) . DIRECTORY_SEPARATOR . $layout;
        return new self($layout);
    }

}