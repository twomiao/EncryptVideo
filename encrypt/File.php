<?php

declare(strict_types=1);

namespace Compiler\encrypt;

class File extends \SplFileInfo
{
    public const SEEK_CUR = 1;
    public const SEEK_SET = 0;
    public const SEEK_END = 2;

    private int $proportion = 30;

    private $handler;

    private string $mode = '';

    public function __construct(protected string $originFilename)
    {
        parent::__construct($originFilename);
    }

    public function setOriginFilename(string $filename) :void
    {
        $this->originFilename = $filename;
    }

    public function getOriginFilename() : string {
        return $this->originFilename;
    }
    /**
     * @param int $size
     * @return bool|string
     * @throws \Exception
     */
    public function read(int $size) : bool|string {
        if ($size <1)
        {
            $size = 1024*1024*10;
        }
        $mode = match($this->mode)
        {
            'w','w+' => $this->close(),
            default => 'r'
        };
        $this->openResource($mode);
        return fread($this->handler, $size);
    }

    public function getTell() : int {
        if (!is_resource($this->handler))
        {
            return 0;
        }
        return (int)ftell($this->handler);
    }

    public function getFileHash(bool $bin = true):string{
        if (!$this->getRealPath())
        {
            throw new \Exception("Failed to open the file.");
        }
        return md5_file($this->getRealPath(), $bin);
    }

    public function close() : bool {
        $isClosed = false;
        if (is_resource($this->handler))
        {
            $isClosed = fclose($this->handler);
        }
        $this->handler = null;
        return $isClosed;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function openResource(string $mode) : void {
        if (!is_resource($this->handler))
        {
            $this->mode = $mode;
            $this->handler = fopen($this->getPathname(), $mode);
            if (!$this->handler) {
                throw new \Exception("Failed to open the file.");
            }
        }
    }

    /**
     * @param int $offset
     * @param int $whence
     * @return void
     * @throws \Exception
     */
    public function setSeek(int $offset, int $whence = self::SEEK_SET) {
        $whence2 = match ($whence) {
            self::SEEK_END => \SEEK_END,
            self::SEEK_CUR => \SEEK_CUR,
            default => \SEEK_SET
        };
        if(!is_resource($this->handler))
        {
            $this->openResource($this->mode);
        }
        fseek($this->handler, $offset, $whence2);
    }

    /**
     * @param string $data
     * @return int
     * @throws \Exception
     */
    public function write(string $data) : int {
        $mode  = match($this->mode)
        {
            'r', 'r+' => $this->close(),
            default => 'w+'
        };
        $this->openResource($mode);
        return (int)fwrite($this->handler, $data);
    }

    public function setProportion(int $proportion) : void {
        if ($proportion < 1 || $proportion > 100) {
            // 20%
            $proportion = 20;
        }
        $this->proportion = $proportion;
    }

    public function getSizeFormat(): string
    {
        $size = $this->getSize();
        if ($size < 1024) {
            return "{$size} Byte";
        }
        $list = ["Kb", "Mb", "Gb"];
        $i = 0;
        while(($size /= 1024) > 1024) {
            $i++;
        }
        $scale = ($i === 2) ? 2 : 0;
        bcscale($scale);
        return bcadd((string)$size, "0") . " {$list[$i]}";
    }

    public function getProportion() : int {
        return $this->proportion; //10%
    }
    public function getProportionValue() : int {
        return (int)($this->getSize() * ($this->proportion / 100)); //10%
    }
}