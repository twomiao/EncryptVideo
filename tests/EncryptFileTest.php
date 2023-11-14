<?php

namespace Tests;

use Compiler\Encrypt\FileHeader;
use Compiler\Encrypt\Utils;
use PHPUnit\Framework\TestCase;

//  // php vendor/bin/phpunit Tests/
class EncryptFileTest extends TestCase
{
    // php vendor/bin/phpunit --filter test_decrypt_file Tests/
    public function test_decrypt_file()
    {
        // $sourcePath = __DIR__ . "/test.txt";
        $sourcePath = __DIR__ . "/test.mp4.zz";
        $source = fopen($sourcePath, "r");
        $zzfile = fopen(__DIR__ . "/测试文件.mp4", "w+");
        $firstBin = unpack("Jfile_eof/Nserial_len", fread($source, 12));
        fseek($source, $firstBin['file_eof']);
        $headerInfo = fread($source, $firstBin['serial_len']);
        $headerInfo = unserialize(Utils::decryptString($headerInfo, '123456'));
        $pointers = $headerInfo['pointers'];
        // 文件总长度
        $file_eof = $firstBin['file_eof'];
        $readBuffer = 10 * 1024 * 1024;
        $hash = $headerInfo['hash'];

        fseek($source, $start = key($pointers));

        $current = $start;
        while(($p = ftell($source)) < $file_eof) {
            if ($current === $p && $length = \current($pointers)) {
                $data = Utils::decryptString(fread($source, $length), '123456');
                \next($pointers);
                $current = \key($pointers);
            } elseif (($r = $current - $p) <  $readBuffer) {
                $data = fread($source, $r);
            } else {
                $data = fread($source, $readBuffer);
            }
            fwrite($zzfile, $data);
        }
        fclose($source);
        $this->assertEquals($hash, md5_file(__DIR__ . "/测试文件.mp4"));
    }

    public function test_encrypt_file()
    {
        // $sourcePath = __DIR__ . "/1.txt";
        $sourcePath = __DIR__ . "/1.mkv";
        $source = fopen($sourcePath, "r");
        $dest = fopen(__DIR__ . "/test.mp4.zz", "w+");
        $readBufferSize = 10 * 1024 * 1024;
        $chunkSize = intval(filesize($sourcePath) / 3);
        $pointer = 0;
        // 6 bytes + datalen + serialize data
        fseek($dest, 12); // 0 1 2 3 4 5

        $headerInfo = new FileHeader($sourcePath);
        // *cdef*3456*9xyz*
        while(!feof($source)) {
            $currentPointer = ftell($source);
            if ($currentPointer === $pointer) {
                $data = fread($source, $readBufferSize);
                $data = Utils::encryptString($data, "123456");
                // 加密数据
                $headerInfo->savePointer(ftell($dest), strlen($data));
                $pointer += $chunkSize; // 下一个指针加密位置
                // continue;
            } elseif(($p = $pointer - $currentPointer) < $readBufferSize) {
                $data = fread($source, $p);
            } else {
                $data = fread($source, $readBufferSize);
            }
            fwrite($dest, $data);
            // 进度条显示
        }
        // 标记写入位置
        $fileEnd  = ftell($dest);
        $serial = Utils::encryptString($headerInfo->serialize(), '123456');
        fwrite($dest, $serial);
        //写入文件头
        rewind($dest);
        $contentLength = strlen($serial);
        $firstBin = pack("JN", $fileEnd, $contentLength);
        fwrite($dest, $firstBin);
        fclose($source);
        fclose($dest);
    }
}
