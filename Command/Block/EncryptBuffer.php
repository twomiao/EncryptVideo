<?php
namespace Compiler\Command\Block;

class EncryptBuffer {
    public string $data;
    private string $password;
    public string $fileHash;
    public string $filename;
    public string $srcFilesize;

    public function __construct(
        string $srcFileSize, string $filename, string $data,
        string $fileHash, string $password)
    {
        $this->srcFilesize = $srcFileSize;
        $this->filename = $filename;
        $this->data = $data;
        $this->password = $password;
        $this->fileHash = $fileHash;
    }

    public function __toString()
    {
        $data = AesUtils::encryptString($this->data, "aes-128-cbc", $this->password);
        $fileNameLen = \strlen($this->filename); // 文件名称长度
        $hashLen     = \strlen($this->fileHash);
        $length      = \strlen($data); // 12 字节
        $srcLength   = $this->srcFilesize; // 原始文件大小
        return pack("N4", $length, $hashLen, $fileNameLen, $srcLength) .
                $this->filename. $this->fileHash . $data;
    }

    public static function decrypt(string $srcFilesize, string $filename, string $filehash,
    string $data, string $password) : EncryptBuffer {
        $filename = AesUtils::decryptString($filename, "aes-128-cbc", $password);
        $filehash = unpack('H*', $filehash)[1];
        $data   = AesUtils::decryptString($data, "aes-128-cbc", $password);
        return new EncryptBuffer($srcFilesize, $filename, $data, $filehash, $password);
    }
}