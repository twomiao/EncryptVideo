<?php

declare(strict_types=1);

namespace Compiler\Encrypt;

use Exception;
use RuntimeException;
use SplFileInfo;
use Throwable;

class FileHeader
{
    protected array $pointers = [];
    protected SplFileInfo $file;

    public function __construct(protected string $filename, protected ?string $password = null)
    {
        $this->file = new SplFileInfo($filename);
    }

    public function serialize()
    {
        return serialize([
            'filesize' => $this->file->getSize(),
           'hash' => md5_file($this->file->getRealPath()),
           'filename' => $this->file->getBasename(),
           'pointers' => $this->pointers
        ]);
    }

    public function savePointer(int $start, int $length): void
    {
        $res = max($start, $length);
        if ($res < 1) {
            throw new Exception("保存点无效:[$start,$length]");
        }
        $this->pointers[$start]  = $length;
    }

    public function info(): Info
    {
        set_error_handler(fn() => null);
        try {
            $handler = fopen($path = $this->file->getRealPath(), "r");
            if($handler === false) {
               throw new RuntimeException("Failed to open file: {$path}.");
            }
            $data = fread($handler, 12);
            if ($data === false) {
                throw new RuntimeException("Failed to read 12 bytes, {$path}.");
            }
            $info  = unpack("Jfile_eof/Nserial_len", $data);
            if($info === false) {
                throw new RuntimeException("unpack error, {$path}.");
            }
            fseek($handler, $info['file_eof']);
            $data = fread($handler, $info['serial_len']);
            if ($data === false) {
                throw new RuntimeException("Failed to read, {$path}.");
            }
            $res = Utils::decryptString($data, $this->password);
            if($res === false) {
                throw new RuntimeException("Decryption failed, {$path}.");
            }
            $info = unserialize($res);
            if ($info === false) {
                throw new RuntimeException("Unserial error, {$path}.");
            }
        }  finally {
            if(is_resource($handler)) {
                fclose($handler);
            }
            restore_error_handler();
        }
        return new Info($info['filename'], $info['filesize'], $info['hash'], $info['pointers']);
    }
}
