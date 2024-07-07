<?php

namespace App\Helpers;

class Stopwords
{
    public static function get()
    {
        return [
            'i', 'a', 'an', 'am', 'and', 'the',
            // ... add more stopwords
        ];
    }
}
