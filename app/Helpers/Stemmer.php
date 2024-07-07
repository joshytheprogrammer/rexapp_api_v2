<?php

namespace App\Helpers;

use TeamTNT\TNTSearch\Stemmer\PorterStemmer;

class Stemmer
{
    public static function stem($word)
    {
        return PorterStemmer::stem($word);
    }
}
