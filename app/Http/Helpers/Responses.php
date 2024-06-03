<?php

namespace App\Http\Helpers;

class Responses
{
    public static function SUCCESS($message = '', $data = '', $code = 200, $success = true) {
        return response()->json(
            [
                'success' => $success,
                'data' => $data,
                'message' => $message
            ]
        , $code);
    }

    public static function ERROR($message = '', $data = '', $errorCode, $code = 400, $success = true) {
        return response()->json(
            [
                'success' => $success,
                'error' => [
                    'errorCode' => $errorCode,
                    'message' => $message,
                    'data' => $data
                ]
            ]
        , $code);
    }
}
