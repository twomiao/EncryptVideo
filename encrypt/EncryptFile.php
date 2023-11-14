<?php
declare(strict_types=1);

namespace Compiler\encrypt;

class EncryptFile extends File
{
    private int $proportion = 30;

    public function __construct(protected string $filename)
    {
        parent::__construct($filename);
    }

    /**
     * @param string $password
     * @return bool|array
     * @throws \Exception
     */
    public function getEncryptInfo(string $password) : bool|array
    {
        $fileLength = @unpack('JfileLength', $this->read(8));
        if($fileLength === false)
        {
            return false;
        }
        $fileLength = array_pop($fileLength);
        // encrypted info
        $this->setSeek($fileLength);
        $buffer = "";
        while($info = $this->read(8192)) {
            $buffer .= $info;
        }
        if (!$buffer)
        {
            return false;
        }
        $encryptedInfo = @unserialize(Utils::decryptString($buffer, $password));
        if (!$encryptedInfo)
        {
            return false;
        }
        $this->setSeek(8);

        return $encryptedInfo;
    }

    public function setProportion(int $proportion): void
    {
        if ($proportion < 1 || $proportion > 100) {
            // 20%
            $proportion = 20;
        }
        $this->proportion = $proportion;
    }

    public function getProportion(): int
    {
        return $this->proportion; //10%
    }

    public function getProportionValue(): int
    {
        return (int)($this->getSize() * ($this->proportion / 100)); //10%
    }
}