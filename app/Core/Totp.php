<?php
namespace App\Core;

/**
 * Basit TOTP üretici/doğrulayıcı.
 */
class Totp
{
    private const INTERVAL = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $length = 20): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    public static function getQrUri(string $secret, string $email, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $issuerParam = rawurlencode($issuer);
        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s&period=%d&digits=%d', $label, $secret, $issuerParam, self::INTERVAL, self::DIGITS);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / self::INTERVAL);

        for ($i = -$window; $i <= $window; $i++) {
            $calculated = self::generateCode($secret, $timeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    public static function generateCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice ??= (int) floor(time() / self::INTERVAL);
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = substr($hash, $offset, 4);
        $value = unpack('N', $truncated)[1];
        $value &= 0x7fffffff;
        $mod = $value % (10 ** self::DIGITS);

        return str_pad((string) $mod, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $flipped = array_flip(str_split($alphabet));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            if (!isset($flipped[$char])) {
                continue;
            }
            $buffer = ($buffer << 5) | $flipped[$char];
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
