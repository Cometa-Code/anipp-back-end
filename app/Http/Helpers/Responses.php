<?php

namespace App\Http\Helpers;

class Responses
{
    public static function OK($message = '', $data = '') {
        return response()->json(
            [
                'data' => $data,
                'message' => $message,
                'status' => 200
            ]
        , 200);
    }

    public static function CREATED($message = '', $data = '') {
        return response()->json(
            [
                'data' => $data,
                'message' => $message,
                'status' => 201
            ]
        , 201);
    }

    public static function BADREQUEST($message = '', $data = '') {
        return response()->json(
            [
                'data' => $data,
                'message' => $message,
                'status' => 400
            ]
        , 400);
    }
}
