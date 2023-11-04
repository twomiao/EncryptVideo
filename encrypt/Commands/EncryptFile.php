<?php

declare(strict_types=1);

namespace Compiler\Encrypt\Commands;

use Compiler\Encrypt\Encryptor;
use Compiler\Encrypt\Utils;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptFile extends Command
{
    protected function configure()
    {
        $this->setName("encrypt:file")
            ->setDescription("加密视频文件");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = Utils::scanFiles(__DIR__ . "/../../videos", "mp4");

        if (empty($files))
        {
            $output->writeln("<comment>未发现任何视频文件!</comment>");
            return static::SUCCESS;
        }

        static::showFileTable($output, ...$files);

        foreach ($files as $id => $file) {
            // create a progressBar
            $progressBar = new ProgressBar($output, $file->getSize());
            $output->writeln("<question>「{$id}-{$file->getBasename(".mp4")}」：</question>");
            $progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%");

            $encryptor = new Encryptor("123456", 5);
            $encryptor->setProgressBar($progressBar);
            $sourcePath = $file->getRealPath();
            $destPath = $file->getPath() . "/" . uniqid() . ".tmp";
            if ($encryptor->encrypt($sourcePath, $destPath)) {
                $newPath = $file->getPath() . "/" . hash_file('md5', $destPath) . ".zz";
                \rename($destPath, $newPath);
            } else {
                $progressBar->setMessage("<error>「加密失败」</error>");
            }
            $output->writeln("");
        }
        return static::SUCCESS;
    }

    protected static function sizeFormat(int $size): string
    {
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

    protected static function showFileTable(OutputInterface $output, SplFileInfo ...$files): void
    {
        $fileList = [];
        foreach ($files as $id => $file) {
            $file = array(
                'id' =>  $id,
                'filename' => $file->getBasename(),
                "filesize" => static::sizeFormat($file->getSize()),
                "createtime"  => date('Y-m-d H:i:s', $file->getCTime())
            );

            $fileList[] = $file;
        }

        $table = new Table($output);
        $table->setHeaders(array('ID', '文件名', '文件大小', '创建日期'))
        ->setRows($fileList);
        $table->setStyle('borderless');
        $table->render();
    }
}
