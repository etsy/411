<?php

namespace ESQuery;

class Util {
    public static function get($arr, $key, $default=null) {
        return array_key_exists($key, $arr) ? $arr[$key]:$default;
    }

    public static function exists($arr, $key) {
        return array_key_exists($key, $arr);
    }

    // Escape special characters in a query.
    public static function escapeString($str) {
        return str_replace([
            '\\', '+', '-', '=', '&&', '||', '>', '<', '!', '(', ')',
            '{', '}', '[', ']', '^', '"', '~', '*', '?', ':',
            '/', ' '
        ], [
            '\\\\', '\\+', '\\-', '\\=', '\\&&', '\\||', '\\>', '\\<', '\\!', '\\(', '\\)',
            '\\{', '\\}', '\\[', '\\]', '\\^', '\\"', '\\~', '\\*', '\\?', '\\:',
            '\\/', '\\ '
        ], $str);
    }

    // Escape special characters in an array of query chunks.
    public static function escapeGroup($arr) {
        return implode('', array_map(function($x) {
            if(is_string($x)) {
                return Util::escapeString($x);
            } else if($x == Token::W_STAR) {
                return '*';
            } else if ($x == Token::W_QMARK) {
                return '?';
            }
        }, $arr));
    }

    // Given two timestamps, return the inclusive list of dates between them.
    public static function getIndices($from_ts, $to_ts) {
        $dates = [];
        $current = new \DateTime("@$from_ts");
        $to = new \DateTime("@$to_ts");
        // Zero out the time component.
        $current->setTime(0, 0);
        $to->setTime(0, 0);
        while ($current <= $to) {
            $dates[] = $current->format('Y.m.d');
            $current = $current->modify('+1day');
        }
        return $dates;
    }

    // Parser helper. Flatten results into an array.
    public static function combine($first, $rest, $idx) {
        $ret = [];
        $ret[] = $first;

        foreach($rest as $val) {
            $ret[] = $val[$idx];
        }
        return $ret;
    }

    // Parser helper. Turn results into an associative array.
    public static function assoc($first, $rest, $idx) {
        $ret = [];
        $ret[$first[0]] = $first[1];

        foreach($rest as $val) {
            $ret[$val[$idx][0]] = $val[$idx][1];
        }
        return $ret;
    }
}
