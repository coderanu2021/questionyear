<?php

namespace App;

class IndusValleyContent
{
    public static function clean(string $html): string
    {
        return str_replace([
            ' modify this content',
            'It is also know as  also known as',
            'Mesopotamian mentioned the wealthy thir partner',
            'ivory,lapis lazuli, and cotton',
            'The historians  confirm that meluhha was the indus velly.',
            'Around 1900 BCE, the Indus Valley Civilization began to enter a period of gradual decline.Some',
        ], [
            '',
            'It is also known as',
            'Mesopotamian texts mentioned their wealthy trading partner',
            'ivory, lapis lazuli, and cotton',
            'Historians associate Meluhha with the Indus Valley Civilization.',
            'Around 1900 BCE, the Indus Valley Civilization began to enter a period of gradual decline. Some',
        ], $html);
    }
}
