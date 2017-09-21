<?php

namespace app\Common;

class Tools
{
    static public function strToBoolean($str)
    {
        return isset($str) && in_array($str, ['yes', 'true']);
    }

    static public function cnNum2Num($cnNum)
    {
        $cnNums = ['零','一','二','三','四','五','六','七','八','九','十'];
        $enNums = [0,1,2,3,4,5,6,7,8,9,0];
        return str_replace($cnNums, $enNums, $cnNum);
    }

    static public function getDatesByNums($dayNumsStr, $splide = ',', $dayFormat = 'Y-m-d')
    {
        $days = [];
        $dayNums = explode($splide, $dayNumsStr);
        if ($dayNums && is_array($dayNums)) {
            foreach ($dayNums as $dayNum) {
                $day = date($dayFormat, strtotime($dayNum . " day"));
                if (!in_array($day, $days)) {
                    array_push($days, $day);
                }
            }
        }
        return $days;
    }

    static public function getDatesByArrs($dayNums, $dayFormat = 'Y-m-d')
    {
        $days = [];
        if ($dayNums && is_array($dayNums)) {
            foreach ($dayNums as $dayNum) {
                $day = date($dayFormat, strtotime($dayNum . " day"));
                if (!in_array($day, $days)) {
                    array_push($days, $day);
                }
            }
        }
        return $days;
    }

    static public function getDateOptionsByArrs($dayNums, $dayFormat = 'Y-m-d')
    {
        $days = [];
        if ($dayNums && is_array($dayNums)) {
            foreach ($dayNums as $dayNum) {
                $day = date($dayFormat, strtotime($dayNum . " day"));
                \Log::info($day);
                if (!in_array($day, $days)) {
                    $days = array_add($days, $day, $day);
                }
            }
        }
        return $days;
    }

    static public function replaceBlackStr($str, $place = '')
    {
        return preg_replace('/[\n\r\t]/', $place, trim($str));
    }

    static public function getEpisodeByTitle($title)
    {
        $title = Tools::replaceBlackStr($title);
        if (preg_match('/更新([至|到|第]*)(\d+)集/', $title, $matches)) {
            if (isset($matches[2])) {
                return ['episode' => ($matches[2]), 'isEnd' => false];
            }
        }
        if (preg_match('/全(\d+)集/', $title, $matches)) {
            if (isset($matches[1])) {
                return ['episode' => intval($matches[1]), 'isEnd' => true];
            }
        }
        if (preg_match('/(\d+)集全/', $title, $matches)) {
            if (isset($matches[1])) {
                return ['episode' => intval($matches[1]), 'isEnd' => true];
            }
        }
        if (preg_match('/第(\d+)集/', $title, $matches)) {
            if (isset($matches[1])) {
                return ['episode' => intval($matches[1]), 'isEnd' => false];
            }
        }
        if (preg_match('/_(\d+)/', $title, $matches)) {
            if (isset($matches[1])) {
                return ['episode' => intval($matches[1]), 'isEnd' => false];
            }
        }
        return [];
    }

    static public function getAbsoluteUrl($url)
    {
        $url = trim($url);
        $ss = preg_split("/(\?|\&|\#|\%)/", $url);
        if (is_array($ss) && count($ss) > 1) {
            return $ss[0];
        }
        return $url;
    }

    static public function getArrayTextByDom($dom, $glue = ',', $node = "a")
    {
        $aa = [];
        $as = $dom->find($node);
        if (!$as) {
            return Tools::replaceBlackStr($dom->plaintext);
        }
        foreach ($as as $a) {
            array_push($aa, trim($a->plaintext));
        }
        return implode($glue, $aa);
    }

    static public function getArrayByDom($dom, $node = "a")
    {
        $aa = [];
        $as = $dom->find($node);
        if (!$as) {
            return [];
        }
        foreach ($as as $a) {
            array_push($aa, Tools::replaceBlackStr($a->plaintext));
        }
        return $aa;
    }

    static public function getMoviePageType($url)
    {
        $url = Tools::getAbsoluteUrl($url);
        if (strpos($url, "http://www.iqiyi.com/a_") !== false) {
            $url = str_replace("http://www.iqiyi.com/a_", '', $url);
            $subUrl = str_replace(".html", "", $url);
            return [
                'sp_code' => 'iqiyi',
                'type' => 'album',
                'album_id' => $subUrl,
                'video_id' => null
            ];
        } else if (strpos($url, "http://www.iqiyi.com/v_") !== false) {
            $url = str_replace("http://www.iqiyi.com/v_", '', $url);
            $subUrl = str_replace(".html", "", $url);
            return [
                'sp_code' => 'iqiyi',
                'type' => 'album',
                'album_id' => null,
                'video_id' => $subUrl
            ];
        } else if (strpos($url, 'http://v.baidu.com/comic/') !== false) {
            $url = str_replace("http://v.baidu.com/comic/", '', $url);
            $subUrl = str_replace(".htm", "", $url);
            return [
                'sp_code' => 'baidu',
                'type' => 'album',
                'model' => 'cartoon',
                'album_id' => $subUrl,
                'video_id' => null,
            ];
        } else if (strpos($url, 'http://v.baidu.com/show/') !== false) {
            $url = str_replace("http://v.baidu.com/show/", '', $url);
            $subUrl = str_replace(".htm", "", $url);
            return [
                'sp_code' => 'baidu',
                'type' => 'album',
                'model' => 'television',
                'album_id' => $subUrl,
                'video_id' => null,
            ];
        } else if (strpos($url, 'http://v.baidu.com/movie/') !== false) {
            $url = str_replace("http://v.baidu.com/movie/", '', $url);
            $subUrl = str_replace(".htm", "", $url);
            return [
                'sp_code' => 'baidu',
                'type' => 'album',
                'model' => 'film',
                'album_id' => $subUrl,
                'video_id' => null,
            ];
        } else if (strpos($url, 'http://v.baidu.com/tv/') !== false) {
            $url = str_replace("http://v.baidu.com/tv", '', $url);
            $subUrl = str_replace(".htm", "", $url);
            return [
                'sp_code' => 'baidu',
                'type' => 'album',
                'model' => 'teleplay',
                'album_id' => $subUrl,
                'video_id' => null,
            ];
        } else if (strpos($url, 'http://www.mgtv.com/b') !== false) {
            //$url = 'http://www.mgtv.com/b/311718/3815458.html';
            //$url = 'http://www.mgtv.com/b/312814/3860127.html';
            $url = str_replace("http://www.mgtv.com/b", '', $url);
            $subUrl = str_replace(".html", "", $url);
            $subs = explode("/", $subUrl);
            if ($subs && count($subs) == 3) {
                $album_id = intval(trim($subs[1]));
                $video_id = intval(trim($subs[2]));
                return [
                    'sp_code' => 'mgtv',
                    'type' => 'video',
                    'album_id' => $album_id,
                    'video_id' => $video_id,
                    'album_url' => "http://www.mgtv.com/h/" . $album_id . ".html"
                ];
            } else {
                return null;
            }
        } else if (strpos($url, 'http://www.mgtv.com/h') !== false) {
            //$url = 'http://www.mgtv.com/h/311718.html';
            //$url = 'http://www.mgtv.com/h/312814.html';
            $url = str_replace("http://www.mgtv.com/h", '', $url);
            $subUrl = str_replace(".html", "", $url);
            $subs = explode("/", $subUrl);
            if ($subs && count($subs) == 2) {
                $album_id = intval(trim($subs[1]));
                return [
                    'sp_code' => 'mgtv',
                    'type' => 'album',
                    'album_id' => $album_id,
                ];
            } else {
                return null;
            }
        } else if (strpos($url, 'https://v.qq.com/x/cover') !== false) {
            //$url = 'https://v.qq.com/x/cover/28hmf4n505or3n0.html';
            $url = str_replace("https://v.qq.com/x/cover", '', $url);
            $subUrl = str_replace(".html", "", $url);
            $subs = explode("/", $subUrl);
            if ($subs && count($subs) == 2) {
                $album_id = trim($subs[1]);
                return [
                    'sp_code' => 'tencent',
                    'type' => 'album',
                    'album_id' => $album_id,
                ];
            } else {
                return null;
            }
        } else if (strpos($url, 'http://v.qq.com/detail/s') !== false) {
            //$url = 'http://v.qq.com/detail/s/s01ie35i6el3rls.html';
            $url = str_replace("http://v.qq.com/detail/s", '', $url);
            $subUrl = str_replace(".html", "", $url);
            $subs = explode("/", $subUrl);
            if ($subs && count($subs) == 2) {
                $album_id = trim($subs[1]);
                return [
                    'sp_code' => 'tencent',
                    'type' => 'album',
                    'album_id' => $album_id,
                ];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}