<?php
/**
 * SPT software - Object
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: All function work with Object to simplify the jobs
 * 
 */

namespace SPT\Support;

class FncObject
{
    public static function merge(&$obj1, $obj2)
    {
        foreach($obj2 as $key => $value)
        {
            if(is_object($value)){
                FncObject::merge($obj1->$key, $value);
            } else {
                $obj1[$key] = $value;
            }
        }
    }
}
