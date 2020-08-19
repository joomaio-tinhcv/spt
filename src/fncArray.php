<?php
/**
 * SPT software - Array
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: All function work with Array to simplify the jobs
 * 
 */

namespace SPT;

class FncArray
{
    public static function merge(&$arr1, $arr2)
    {
        foreach($arr2 as $key => $value)
        {
            if(is_array($value)){
                fncArray::merge($arr1[$key], $value);
            } else {
                $arr1[$key] = $value;
            }
        }
    }

    public static function toString($arr, $break = "\n")
    {
        return implode( $break, $arr);
    }

    public static function ifReady($arr)
    {
        return(is_array($arr) && count($arr));
    }
}
