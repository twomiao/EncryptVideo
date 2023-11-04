<?php

declare(strict_types=1);

namespace Compiler\Encrypt;

use Exception;
use SplFileInfo;
use Symfony\Component\Console\Helper\ProgressBar;

class Encryptor
{
    private int $bufferSize = 10485760;
    protected ?ProgressBar $progressBar = null;

    public function __construct(
        protected string $password,
        protected int $chunkCount
    ) {}

    protected function openFile(string $filepath, string $mode)
    {
        $source = fopen($filepath, $mode);
        if ($source === false) {
            throw new Exception("打开文件失败:{$filepath}.");
        }
        return $source;
    }

    public function setProgressBar(ProgressBar $progressBar): void
    {
        $this->progressBar = $progressBar;
    }

    public function encrypt(string $source, string $dest): bool
    {
        $this->progressBar?->setMessage("<comment>「开始加密」</comment>");
        $this->progressBar?->start();
        try {
            // 加密文件头相关信息
            $encrypteFileInfo = new FileHeader($source);
            $chunkSize =  intval(filesize($source) / $this->chunkCount);
            $source    = $this->openFile($source, "r");
            $dest      = $this->openFile($dest, "w+");
            fseek($dest, 12);
            $pointer   = 0;
            while(!feof($source)) {
                $this->progressBar?->setMessage("<comment>「加密进行中」</comment>");
                $currentPointer = ftell($source);
                $encrypted  = false;
                if ($currentPointer === $pointer) {
                    $encrypted = true;
                    // encpryted data 15mb
                    $data = fread($source, $this->bufferSize);
                    $this->progressBar?->advance(strlen($data));
                    // 加密数据
                    $data = Utils::encryptString($data, $this->password);
                    $encrypteFileInfo->savePointer(ftell($dest), $len = strlen($data));
                    $pointer += $chunkSize; // 下一个指针加密位置
                } elseif(($remain = $pointer - $currentPointer) < $this->bufferSize) {
                    $data = fread($source, $remain);
                } else {
                    $data = fread($source, $this->bufferSize);
                }
                // 保存新文件
                $len = fwrite($dest, $data);
                // 进度条显示
                if (!$encrypted) {
                    $this->progressBar?->advance($len);
                }
                $encrypted = false;
            }
            // 加密完成
            if($this->progressBar?->getMaxSteps() === $this->progressBar?->getProgress()) {
                // 标记写入位置
                $eof  = ftell($dest);
                $serial = Utils::encryptString($encrypteFileInfo->serialize(), $this->password);
                fwrite($dest, $serial);
                //写入文件头
                rewind($dest);
                // 文件头部长度 content_length 8字节/文件信息头部加密长度4字节 = 12字节
                fwrite($dest, pack("JN", $eof, strlen($serial)));
            }
            $this->progressBar?->setMessage("<info>「加密完成」</info>");
            $this->progressBar?->finish();
            return true;
        } catch(Exception $e) {
            $this->progressBar?->setMessage("<info>「加密失败」{$e->getMessage()}</info>");
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($dest)) {
                fclose($dest);
            }
        }
        return false;
    }

    public function decrypt(string $sourcePath, string $dest): bool
    {
        $this->progressBar?->setMessage("<comment>「开始解密」</comment>");
        $this->progressBar?->display();
        try {
            $zzfile    = $this->openFile($sourcePath, "r");
            $source    = $this->openFile($dest, "w+");
            $firstBin  = unpack("Jfile_eof/Nserial_len", \fread($zzfile, 12));
            fseek($zzfile, $firstBin['file_eof']);
            $headerInfo = fread($zzfile, $firstBin['serial_len']);
            $headerInfo = unserialize(Utils::decryptString($headerInfo, $this->password));
            $pointers = $headerInfo['pointers'];
            $hash  = $headerInfo['hash'];
            // 文件总长度
            $file_eof = $firstBin['file_eof'];

            \fseek($zzfile, $start = \key($pointers));

            $current = $start;
            while(($p = ftell($zzfile)) < $file_eof) {
                $this->progressBar?->setMessage("<comment>「解密进行中」</comment>");
                if ($current === $p && $length = \current($pointers)) {
                    $data = Utils::decryptString(fread($zzfile, $length), $this->password);
                    \next($pointers);
                    $current = \key($pointers);
                } elseif (($r = $current - $p) <  $this->bufferSize) {
                    $data = fread($zzfile, $r);
                } else {
                    $data = fread($zzfile, $this->bufferSize);
                }
                $len = fwrite($source, $data);
                $this->progressBar?->advance($len);
            }
            $this->progressBar?->setMessage("<comment>「正在校验文件」</comment>");
            $this->progressBar?->display();
            if (md5_file($dest) === $hash) {
                $this->progressBar?->setMessage("<info>「校验完成」</info>");
                $this->progressBar?->display();
                // 「解密完成」
                $this->progressBar?->setMessage("<info>「解密完成」</info>");
                $this->progressBar?->finish();
                return true;
            }
        } catch(Exception $e) {
            $this->progressBar?->setMessage("<error>「解密失败」{$e->getMessage()}</error>");
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($zzfile)) {
                fclose($zzfile);
            }
        }
        return false;
    }
}
