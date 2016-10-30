<?php
namespace ZuoYeah\Parser;

/**
 * 题目解析器
 *
 * @package ZuoYeah\Resolver
 */
class Parser
{
    /**
     * 词法解析器
     *
     * @var Lexer
     */
    protected $lexer;
    /**
     * @var Purifier
     */
    protected $purifier;
    /**
     * @var array
     */
    protected $allowedTypes = [];

    /**
     * @param array $allowedTypes
     */
    function __construct(array $allowedTypes = [])
    {
        $this->lexer = new Lexer();
        $this->purifier = new Purifier();
        $this->allowedTypes = empty($allowedTypes)
            ? self::defaultAllowedTypes()
            : $allowedTypes;
    }

    /**
     * 获取的标签
     *
     * @return array
     */
    static function defaultAllowedTypes()
    {
        return [
            '【单项选择题】' => 'string',
            '【多项选择题】' => 'string',
            '【判断题】'   => 'string',
            '【填空题】'   => 'string',
            '【匹配题】'   => 'string',
            '【作图题】'   => 'string',
            '【计算题】'   => 'string',
            '【简答题】'   => 'string',
            '【论述题】'   => 'string',
            '【实验题】'   => 'string',
            '【作文题】'   => 'string',
            '【改错题】'   => 'string',
            '【套题】'    => 'string',
            '【答案】'    => 'string',
            '【知识点】'   => 'string',
            '【解析】'    => 'string',
            '【学科】'    => 'string',
            '【题号】'    => 'string',
            '【大题结束】'  => 'string',
            '【小题结束】'  => 'string',
            '【选项数量】'  => 'string',
            //'/【[A-Za-z]】/' => 'regex',
        ];
    }

    /**
     * @param $type
     *
     * @return bool
     */
    protected function isAllowedType($type)
    {
        foreach ($this->allowedTypes as $tagValue => $tagType) {
            if ($tagType == 'regex') {
                if (preg_match($tagValue, $type)) {
                    return true;
                }
            } else if ($tagType == 'string') {
                if ($tagValue == $type) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $content
     *
     * @return array
     */
    function parse($content)
    {
        $tokens = $this->lexer->parse($content);
        $sections = [];
        $latest = null;
        foreach ($tokens as $i => $token) {
            if ($this->isAllowedType($token->getType())) {
                $latest = $token;
                $sections[] = $latest;
            } elseif (!is_null($latest)) {
                $latest = array_pop($sections);
                $latest->setValue(
                    $latest->getValue() . $token->raw()
                );
                $sections[] = $latest;
            }
        }
        foreach ($sections as $token) {
            $token->setValue(
                $this->purify($token->getValue())
            );
        }
        return $sections;
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function purify($value)
    {
        $value = $this->purifier->purify($value);
        $value = trim(preg_replace('/\r\n/', '', $value));
        $value = trim(preg_replace('/(<br \/>)+$/', '', $value));
        return $value;
    }
}