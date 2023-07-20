<?php
namespace Compiler\Command\Block;
use SplFileInfo;

class Os {
    public static function listFile(string $filepath,
                                    string $extension = "mp4",
                                    bool $realpath = false) : array|bool {
        $directories = @scandir($filepath, SCANDIR_SORT_NONE );
        if ($directories === false) {
            throw new FileException("directory scan files fail.");
        }
        $files = [];
        foreach ($directories as $file)
        {
            if ($file === "." || $file === "..")
            {
                continue;
            }
            $absFilePath = \rtrim($filepath, "\\/"). DIRECTORY_SEPARATOR.$file;
            if (is_dir($absFilePath))
            {
                $temp = self::listFile($absFilePath, $extension, $realpath);
                $files =  array_merge($files, $temp);
                continue;
            } 
            $absFilePath = ($realpath === true)  ? 
                            \realpath($absFilePath) : $absFilePath;

            $file = new File($absFilePath);
            if ($file->getExtension() === $extension)
            {
                array_push($files, $file);
            }
        }
        return $files;
    }
}