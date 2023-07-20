<?php
namespace Compiler\Command\Block;

class Encryptor {
    protected array $files;
    protected array $idList;
    protected bool $isEqualHash;
    const ENCRYPT_BINARY_LENGTH = 8*1024*1024;   

    public function __construct(bool $isEqualHash = false) {
        $this->files = [];
        $this->idList = [];
        $this->isEqualHash = $isEqualHash;
    }

    public function addFile(File $file) : void {
        if (!file_exists($file)) {
            return;
        }
        // 文件hash 结果比较
        $fileHash = ($this->isEqualHash !== false) ?
                    $file->getFileHash(false) : md5($file->getRealPath());
        if (!in_array($fileHash, $this->idList, true)) {
            $this->idList[] = $fileHash;
            $this->files[$fileHash] = $file;
        }
    }

    public function addFiles(File ...$files) : void {
        // 弹栈
        while($file = \array_shift($files)) {
            $this->addFile($file, $this->isEqualHash);
        }
    }

    public function listFile() : array {
        $fileList = [];
        foreach ($this->idList as $id => $fileHash) {
            if (isset($this->files[$fileHash])) {
                // file[index] = file
                $fileList[$id] = $this->files[$fileHash];  
            }
        }
        return $fileList;
    }

    public function delete($id) : void {
        if (isset($this->files[$id]))
        {
            $fileHash = $this->files[$id];
            unset($this->files[$id]);
            unset($this->idList[$fileHash]);
        }
    }

    public function locked(File $file) : string {
        $data = file_get_contents(
            $path = $file->getRealPath(), 
            false,
            null, 0,
            static::ENCRYPT_BINARY_LENGTH);
        if ($data===false) {
            throw new FileException("打开文件失败: {$path}");
        }
        return (string)new EncryptBuffer(
            $file->getSize(),
            $file->encryptFilename(),
            $data,
            $file->getFileHash(true),
            $file->getNewPassword(),
        );
    }

    public function unlock($srcSize, $filename, $filehash, $data, string $password) : EncryptBuffer {
        return EncryptBuffer::decrypt($srcSize, $filename, $filehash, $data, $password);
    }

    public function __destruct()
    {
        $this->files = $this->idList = [];
    }
    
}