<?php 

namespace App\Support;

class OtpExtractor
{
    public static function extract(?string $text): ?string
    {
        if (!$text) return null;

        // default: 4 digit
        preg_match('/\b\d{4}\b/', $text, $match);

        return $match[0] ?? null;
    }
}

?>