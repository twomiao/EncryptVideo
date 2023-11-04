<?php
declare(strict_types=1);

namespace Compiler\Encrypt;

class Utils
{
    public static function encryptString(string $data, string $key): string
    {
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        return  $iv . openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function decryptString(string $data, string $key): bool|string
    {
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($data, 0, $ivlen);
        $data = substr($data, 16);
        return openssl_decrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    }


    public static function scanFiles(string $dir, string $extension = "mp4"): array
    {
        $directory = new \RecursiveDirectoryIterator($dir);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = array();
        foreach ($iterator as $info) {
            if ($info->isDir() || $info->getExtension() !== $extension) {
                continue;
            }
            $files[] = $info;
        }
        return $files;
    }
}
