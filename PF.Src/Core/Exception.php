<?php

namespace Core;

class Exception
{

    private static $_errors = [];

    public function toss()
    {
        $args = func_get_args();
        $message = $args[0];
        if (is_array($message)) {

            //  comment by Rob, it causes duplicate error messages
            //	self::$_errors = $message;

            $message = implode('', $message);
        }
        unset($args[0]);

        $out = ($args ? vsprintf($message, $args) : $message);

        self::$_errors[] = $out;
        throw new \Exception($out);
    }

    public static function getErrors($html = false)
    {
        if ($html === true) {
            $out = '';
            foreach (self::$_errors as $error) {
                $out .= '<div class="error_message" role="error">' . $error . '</div>';
            }

            return $out;
        }
        return self::$_errors;
    }
}