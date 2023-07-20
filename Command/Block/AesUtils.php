<?php
namespace Compiler\Command\Block;

class AesUtils {
    public static function encryptString(string $data, string $method,
        string $key) : string {
            return base64_encode(openssl_encrypt($data,'aes-128-cbc',$key, 0, str_pad($key, 16)));
    }

    public static function decryptString(string $data, string $method,
        $key): string {
            $data = openssl_decrypt(base64_decode($data),'aes-128-cbc',$key,0,str_pad($key, 16));
            if ($data == false)
            {
                throw new FileException("文件解密失败 [$key].");
            }
            return $data;
    }

}