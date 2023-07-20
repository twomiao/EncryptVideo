<?php
namespace Compiler\Command\Block;

use SplFileInfo;

final class File extends SplFileInfo {
    private string $password; 
    
    public function __construct(string $path)
    {
        parent::__construct($path);
        $this->password = "";
    }

    public function getFileHash(bool $raw = false) : string {
        $hash = \md5_file($this->getRealPath()); 
        if ($raw) {
            $hash = \pack("H*", $hash);
        }
        return $hash;
    }

    public function setNewPassword(string $password) {
        if (strlen($password) !== 9) {
            throw new FileException("文件保护密码只能9位: [{$password}].");
        }
        $this->password = $password;
    }

    public function getNewPassword() : string {
        if (!$this->password) {
            throw new FileException("文件还未设置密码保护.");
        }
        return $this->password;
    }

    public function randomFileName(string $extension = "zz") : string {
    //    return bin2hex(random_bytes(10)). ".{$extension}";
        return md5_file($this->getRealPath()). ".{$extension}";
    }

    public function encryptFilename() : string {
        if (!$this->password) {
            throw new FileException("文件还未设置密码保护.");
        }
        return AesUtils::encryptString($this->getFilename(), "aes-128-cbc", $this->password);
    }
}