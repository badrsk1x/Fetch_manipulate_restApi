<?php

namespace App\Core;

class Decoder
{
    /**
     * Decode JSON
     *
     * @access public
     * @param  $json
     * @param  $assoc
     * @param  $depth
     * @param  $options
     */
    public static function decodeJson()
    {
        $args = func_get_args();

        $response = call_user_func_array('json_decode', $args);
       
        if ($response === null) {
            $response = $args['0'];
        }
        return $response;
    }
}
