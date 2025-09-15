<?php

namespace App\Helpers;

class FlashHelper
{
    public static function success($message)
    {
        session()->flash('success', $message);
    }

    public static function error($message)
    {
        session()->flash('error', $message);
    }

    public static function warning($message)
    {
        session()->flash('warning', $message);
    }

    public static function info($message)
    {
        session()->flash('info', $message);
    }
}
