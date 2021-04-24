<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2021 ~ 2022
 */

namespace BiliHelper\Tool;

class Common
{
    /**
     * @use 替换字符串
     * @param $str
     * @param $start
     * @param int $end
     * @param string $dot
     * @param string $charset
     * @return string
     */
    public static function replaceStar($str, $start, $end = 0, $dot = "*", $charset = "UTF-8")
    {
        $len = mb_strlen($str, $charset);
        if ($start == 0 || $start > $len) {
            $start = 1;
        }
        if ($end != 0 && $end > $len) {
            $end = $len - 2;
        }
        $endStart = $len - $end;
        $top = mb_substr($str, 0, $start, $charset);
        $bottom = "";
        if ($endStart > 0) {
            $bottom = mb_substr($str, $endStart, $end, $charset);
        }
        $len = $len - mb_strlen($top, $charset);
        $len = $len - mb_strlen($bottom, $charset);
        $newStr = $top;
        for ($i = 0; $i < $len; $i++) {
            $newStr .= $dot;
        }
        $newStr .= $bottom;
        return $newStr;
    }


}


