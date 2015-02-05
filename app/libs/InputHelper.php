<?php

/**
 * Created by PhpStorm.
 * User: HUNG NGUYEN
 * Date: 4/7/14
 * Time: 10:34 PM
 */
class InputHelper
{
    private static $input;
    private static $access_token;
    public static function setInputArray( $input ) {
        self::$input = $input;
    }

    public static $ver_availables=array('1.0');

    public static function exist($name) {
        return array_key_exists($name, self::$input);
    }

    public static function getInput($name, $require, $default_value = null)
    {
        if (array_key_exists($name, self::$input)) {
            return self::$input[$name];
        } else {
            if (!$require) {
                return $default_value;
            } else {
                throw new APIException("$name Invalid", APIException::ERRORCODE_LACK_PARAMETER);
            }
        }
    }

    public static function getAccessToken()
    {
        if ( self::$access_token ) return self::$access_token;

        $header = Request::header();

        if ( Input::has('access_token')) {
            return Input::get('access_token');
        } elseif ( Input::has('accesstoken')) {
            return Input::get('accesstoken');
        } elseif (array_key_exists('access_token', $header)) {
            return $header['access_token'][0];
        } elseif (array_key_exists('accesstoken', $header)) {
            return $header['accesstoken'][0];
        } else {
            return null;
        }
    }
    public static function setAccessToken( $token ) {
        self::$access_token = $token;
    }
    public static function getFile($filename,$require=false)
    {
        if(Input::hasFile($filename)) {
            return Input::file($filename);
        } elseif($require) {
            throw new APIException("$filename not attachment", APIException::ERRORCODE_LACK_PARAMETER);
        } else {
            return null;
        }
    }
    public static function getAllInput()
    {
        return self::$input;
    }
}
