<?php
namespace ZuoYeah\Parser;

class Lexer
{
    /**
     * @var string
     */
    protected $leftDelimiter = '【';
    /**
     * @var string
     */
    protected $rightDelimiter = '】';

    protected $encoding = 'utf-8';

    /**
     * @param string $encoding
     */
    function __construct($encoding = 'utf-8')
    {
        $this->encoding = $encoding;
    }

    function parse($value)
    {
        $delimiters = $this->splitDelimiters($value);
        $groups = $this->groupDelimiters($delimiters);
        return $this->parseTokens($groups, $value);
    }

    protected function parseTokens(array $groups, $value)
    {
        $tokens = [];
        for ($i = 0, $c = count($groups); $i < $c; $i++) {
            $current = $groups[$i];
            $next = $i == $c - 1 ? null : $groups[$i + 1];

            $type = mb_substr($value,
                $current['left'],
                $current['right'] - $current['left'] + 1,
                $this->encoding
            );

            if (!is_null($next)) {
                $text = mb_substr(
                    $value,
                    $current['right'] + 1,
                    $next['left'] - $current['right'] - 1,
                    $this->encoding
                );
            } else {
                $text = mb_substr(
                    $value,
                    $current['right'] + 1,
                    mb_strlen($value, $this->encoding) - $current['right'] - 1,
                    $this->encoding
                );
            }

            $tokens[] = new Token($type, $text);
        }
        return $tokens;
    }

    protected function groupDelimiters(array $delimiters)
    {
        $groups = [];
        $left = null;
        $right = null;
        foreach ($delimiters as $i => $item) {
            if ($i == 0 && $item['delimiter'] == $this->rightDelimiter) {
                $left = [
                    'delimiter' => $this->leftDelimiter,
                    'position'  => 0
                ];
                $right = $item;
            } else {
                if ($item['delimiter'] == $this->leftDelimiter) {
                    $left = $item;
                    continue;
                }
                if (!is_null($left) && $item['delimiter'] == $this->rightDelimiter) {
                    $right = $item;
                } else {
                    continue;
                }
            }

            $groups[] = [
                'left'  => $left['position'],
                'right' => $right['position']
            ];

            $left = null;
            $right = null;
        }
        return $groups;
    }

    protected function splitDelimiters($value)
    {
        $delimiters = [];
        for ($i = 0, $l = mb_strlen($value, $this->encoding); $i < $l; $i++) {
            $char = mb_substr($value, $i, 1, $this->encoding);
            if ($char == $this->leftDelimiter ||
                $char == $this->rightDelimiter
            ) {
                $delimiters[] = [
                    'delimiter' => $char,
                    'position'  => $i
                ];
            }
        }
        return $delimiters;
    }
}