<?php 

namespace App\Support;

class OtpExtractor
{
    /**
     * Ekstrak OTP dari teks secara dinamis.
     * Prioritas: 6 digit (paling umum) → 4-8 digit dengan konteks → pure digit 4-8.
     * Hasilnya mengikuti panjang digit yang benar-benar ada di email.
     */
    public static function extract(?string $text): ?string
    {
        if (!$text) return null;

        $text = strip_tags(html_entity_decode($text));

        $patterns = [
            // 1. 6 digit dengan konteks kata kunci (paling umum untuk OTP)
            '/(?:code|otp|verification|pin|token|kode)[:\s]*[#]?\s*[:\-]?\s*(\d{6})\b/i',
            // 2. 6 digit setelah "your/the/is"
            '/(?:your|the|is)[:\s]+(\d{6})\s*(?:is|\.|\s|$)/i',
            // 3. 6 digit standalone (paling banyak match OTP 6 digit)
            '/\b(\d{6})\b/',
            // 4. 4-8 digit dengan konteks kata kunci (fallback)
            '/(?:code|otp|verification|pin|token|kode)[:\s]*[#]?\s*[:\-]?\s*(\d{4,8})\b/i',
            // 5. 4-8 digit standalone (fallback terakhir)
            '/\b(\d{4,8})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $otp = $match[1];
                if (strlen($otp) >= 4 && strlen($otp) <= 8) {
                    return $otp;
                }
            }
        }

        return null;
    }
}

?>