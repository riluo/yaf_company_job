<?php
namespace Gram\Utility\Helper;

class ImageHelper
{
    static function getColBlackPercent($colors, $colIndex)
    {
        $blackCount = 0;
        $height = count($colors);
        for ($i = 0; $i < $height; $i++) {
            if ($colors[$i][$colIndex]) {
                $blackCount++;
            }
        }

        if ($blackCount / $height > 0) {
//            DebugHelper::log("getColBlackPercent: $colIndex " . $blackCount / $height);
        }

        return $blackCount / $height;
    }

    static function getRowBlackPercent($colors, $rowIndex)
    {
        $blackCount = 0;
        $width = count($colors[0]);
        for ($i = 0; $i < $width; $i++) {
            if ($colors[$rowIndex][$i]) {
                $blackCount++;
            }
        }

        if ($blackCount / $width > 0) {
//            DebugHelper::log("getRowBlackPercent: $rowIndex " . $blackCount / $width);
        }


        return $blackCount / $width;
    }

    static function isSame($colors, $rowIndex1, $colIndex1, $rowIndex2, $colIndex2)
    {
        return $colors[$rowIndex1][$colIndex1] == $colors[$rowIndex2][$colIndex2];
    }


    static function getColDiffPercent($colors, $colIndex1, $colIndex2)
    {
        $diffCount = 0;
        $height = count($colors);
        for ($i = 0; $i < $height; $i++) {
            if (!self::isSame($colors, $i, $colIndex1, $i, $colIndex2)
            ) {
                $diffCount++;
            }
        }

        if ($diffCount / $height > 0) {
//            DebugHelper::log("getColDiffPercent: $colIndex1, $colIndex2 " . $diffCount / $height);
        }
        return $diffCount / $height;
    }

    static function getRowDiffPercent($colors, $rowIndex1, $rowIndex2)
    {
        $diffCount = 0;
        $width = count($colors[0]);
        for ($i = 0; $i < $width; $i++) {
            if (!self::isSame($colors, $rowIndex1, $i, $rowIndex2, $i)
            ) {
                $diffCount++;
            }
        }

        if ($diffCount / $width > 0) {
//            DebugHelper::log("getRowDiffPercent: $rowIndex1, $rowIndex2 " . $diffCount / $width);
        }
        return $diffCount / $width;
    }


    //通用二值化处理  参数：图片样式，二值化分母值（255为居中，值越大越想黑色接近）
    public static function getHec_255($file, $type, $m255 = 255)
    {

        switch ($type) {
            case "png":
                $res = imagecreatefrompng($file);
                break;
            case "gif":
                $res = imagecreatefromgif($file);
                break;
            default:
                $res = imagecreatefromjpeg($file);
                break;
        }

        $size = getimagesize($file);
        $data = array();

        //二值化和孤立点参数
        //$m255=255;
        //$gulidian=4;
        //二值化
        for ($i = 0; $i < $size[1]; ++$i) {
            for ($j = 0; $j < $size[0]; ++$j) {
                $rgb = imagecolorat($res, $j, $i);

                $rgbarray = imagecolorsforindex($res, $rgb);
                $r = $rgbarray['red'] * 0.333;
                $g = $rgbarray['green'] * 0.333;
                $b = $rgbarray['blue'] * 0.333;
                $t = round(($r + $g + $b) / $m255);

                if ($t < 50 / 255) {
                    $data[$i][$j] = 1;
                } else {
                    $data[$i][$j] = 0;
                }
            }
        }

        return ['colors' => $data,
            'size' => $size
        ];
    }


    static function getColorArray(\Imagick $img)
    {
        $colors = [];
        $iterator = $img->getPixelIterator();
        foreach ($iterator as $row => $pixels) {
            foreach ($pixels as $col => $pixel) {
                /** @var $pixel \ImagickPixel */

                $color = $pixel->getColor();

                $colors[$row][$col] = $color['r'] + $color['g'] + $color['b'] < 50;
            }

            $iterator->syncIterator();
        }

        return $colors;
    }

    static function nextWhiteBlockToBottom($colors, $fromIndex, $colIndex, $whiteLimit)
    {
        $height = count($colors);
        $notBlackCount = 0;
        for ($i = $fromIndex; $i < $height; $i++) {
            if ($colors [$i][$colIndex]) {
                $notBlackCount = 0;
            } else {
                $notBlackCount++;
            }
            if ($notBlackCount > $whiteLimit) {
                return $i - $whiteLimit;
            }
        }
        return $height - $notBlackCount;
    }
}