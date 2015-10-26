<?php

namespace App\Helpers;

class StringHelper {

    /**
     * Generate Unique Code
     *
     * @params string prefix
     * @return string
     */
    function uniqueKey($prefix = 'pass-') {

        return uniqid($prefix);
    }

    /**
     * Generate Random Code
     *
     * @params int
     * @return string
     */
    function randomCode($length = 6) {

        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $random_str = '';
        for ($i = 0; $i < $length; $i++) {
            $random_str .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $random_str;
    }

    /**
     * Generate Random Digits
     *
     * @params int
     * @return string
     */
    function randomDigits($length = 6) {

        $characters = '0123456789';
        $random_str = '';
        for ($i = 0; $i < $length; $i++) {
            $random_str .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $random_str;
    }

    /**
     * Generate Random String
     *
     * @params int
     * @return string
     */
    function randomString($length = 6) {

        $random_str = str_random($length);
        return $random_str;
    }

    /**
     * Encrypt plain text
     *
     * @param $plainString
     * @return string
     */
    function encryptString($plainString) {

        return sha1($plainString);
    }

    /**
     * Hash plain text
     *
     * @param $plainString
     * @return string
     */
    function hashString($plainString) {

        return md5($plainString);
    }

    /**
     * Trim a long string
     *
     * @param $string
     * @param $length
     * @return string
     */
    function trimString($string, $length)
    {
        if(strlen($string)>$length) {
            $result = substr($string, 0, strpos($string, ' ', $length));
            return $result . ' ...';
        }
        else {
            return $string;
        }
    }
}