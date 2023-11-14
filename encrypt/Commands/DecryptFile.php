<?php

declare(strict_types=1);

namespace Compiler\Encrypt\Commands;

use Compiler\Encrypt\Encryptor;
use Compiler\Encrypt\FileHeader;
use Compiler\Encrypt\Utils;
use Exception;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecryptFile extends Command
{
    protected function configure()
    {
        $this->setName("decrypt:file")
            ->setDescription("解密视频文件");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        ini_set('date.timezone', 'Asia/Shanghai');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $password = "123456";
        $suffix = "zz";
        $encryptedFiles = Utils::scanFiles(__DIR__ . "/../../videos", $suffix);
        $files = static::showFiles($password, ...$encryptedFiles);
        if (empty($files))
        {
            $output->writeln("<comment>未发现任何可解密文件!</comment>");
            return static::SUCCESS;
        }
        $table = new Table($output);
        $table->setHeaders(array('ID', '文件名', '加密文件名', '文件大小', '创建日期'))
        ->setRows((function ($files) {
            foreach ($files as $k => $file) {
                $files[$k]['filesize'] = static::sizeFormat($file['filesize']);
            }
            return $files;
        })($files));
        $table->setStyle('borderless');
        $table->render();

        foreach ($encryptedFiles  as $id => $file) {
            $fileInfo = $files[$file->getBasename()];
            // create a progressBar
            $progressBar = new ProgressBar($output, $fileInfo['filesize']);
            $output->writeln("<question>「{$id}-{$fileInfo['filename']}」：</question>");
            $progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%");
            $progressBar->setMessage("<comment>「正在校验加密文件」</comment>");
            $progressBar->start();
            if ($file->getBasename(".zz") !== md5_file($file->getRealPath())) {
                $progressBar?->setMessage("<error>「加密文件已损坏」</error>");
                $progressBar?->display();
                $output->writeln("");
                continue;
            }

            $encryptor = new Encryptor($password, 5);
            $encryptor->setProgressBar($progressBar);
            $sourcePath = $file->getRealPath();
            $destPath = $file->getPath() . "/{$fileInfo['filename']}";
            if (!$encryptor->decrypt($sourcePath, $destPath)) {
                $progressBar->setMessage("<error>「解密失败」</error>");
                $progressBar->display();
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

    protected static function showFiles(string $password, SplFileInfo ...$files): array
    {
        $fileList = [];
        foreach ($files as $id => $file) {
            try {
                $header = new FileHeader($file->getRealPath(), $password);
                $header->info();
                $file = array(
                    'id' =>  $id,
                    'filename' => $header->info()->filename,
                    'encrypt_filename' => $file->getBasename(),
                    'filesize' => $header->info()->filesize,
                    "createtime"  => date('Y-m-d H:i:s', $file->getCTime())
                );

                $fileList[$file['encrypt_filename']] = $file;

            } catch(Exception $e) {

            }
        }
        return  $fileList;
    }
}
