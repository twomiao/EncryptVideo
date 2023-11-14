<?php

declare(strict_types=1);

namespace Compiler\encrypt\Commands;

use Compiler\encrypt\Chunk;
use Compiler\encrypt\File;
use Compiler\Encrypt\Utils;
use JetBrains\PhpStorm\NoReturn;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FastEncrypt extends Command
{
    private OutputInterface $output;
    protected function configure()
    {
        $this->setName("fast:encrypt")
            ->setDescription("快速加密视频文件");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        $this->output = $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = Utils::scanFiles(__DIR__ . "/../../videos", "mp4");

        if (empty($files))
        {
            $output->writeln('<bg=yellow;options=bold>未发现任何视频文件!</>');
            return self::SUCCESS;
        }

        $files = self::showFileTable($output, ...$files);

        /**
         * @var SplFileInfo $file
         */
        foreach ($files as $file) {
            try {
                clearstatcache();
                if(!$file->isFile())
                {
                    $this->output->writeln("<error>文件已被删除</error>");
                    continue;
                }
                // 原文件
                $originFile = new File($file->getRealPath());
                $originFile->setProportion(100);    // 加密50%部分
                // 目标文件
                $targetFile = new File($file->getPath().DIRECTORY_SEPARATOR.uniqid().".zz");
                // 开始加密
                $this->fastEncrypt($originFile, $targetFile, '995200452');
            } catch (\Exception $e) {
                $output->write("<bg=red;options=bold>{$e->getMessage()}</>");
            }
            $output->writeln("");
        }
        return self::SUCCESS;
    }

    /**
     * @param File $origin
     * @param File $target
     * @return bool
     * @throws \Exception
     */
    #[NoReturn]
    protected function fastEncrypt(File $origin, File $target, string $password)  : bool {
        // 创建进度条
        $progressBar = new ProgressBar($this->output, $origin->getSize());
        $this->output->writeln("<question>「{$origin->getBasename(".mp4")}」：</question>");
        $progressBar->setMessage("<info>「计算文件哈希」</info>");
        $progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%");
        $progressBar->display();
        $size = 20*1024*1024;
        $encryptedInfo = [
            'filename' => $origin->getFilename(),
            'hash' => $origin->getFileHash(true),
            'info'=>[],
        ];
        $fileLength = 0;

        $progressBar->setMessage("<info>「开始加密」</info>");
        $target->openResource('w');
        // 头部预留8字节
        $target->setSeek(8);
        $proportion = $origin->getProportionValue();
        $bufferSize = $size;
        do{
            $progressBar->setMessage("<info>「加密中」</info>");
            $data = $origin->read($bufferSize);
            $step = strlen($data);
            $tell = $origin->getTell();
            // 加密数据
            if (($r=($proportion - $tell)) <= $bufferSize) {
                $bufferSize = $r;
            }
            $encrypted = Utils::encryptString($data, $password);

            $encryptedDataLength = strlen($encrypted);
            $fileLength += $encryptedDataLength;
            $encryptedInfo['info'][] = new Chunk($encryptedDataLength, true);
//            var_dump($data);
            $target->write($encrypted);
            $progressBar->advance($step);
            $progressBar->display();
        } while($proportion > $tell);

        while($origin->getProportion() < 100 && $data = $origin->read($size))
        {
            $len = $target->write($data);
            $encryptedInfo['info'][] = new Chunk($len, false);
            $fileLength += $len;
            $progressBar->advance($step);
            $progressBar->display();
        }
        // [J+data+info]
        $target->write(Utils::encryptString(serialize($encryptedInfo), $password));
        $target->setSeek(0);
        $target->write( pack("J", $fileLength+8) );
        $progressBar->setMessage("<info>「加密完成」</info>");
        $progressBar->finish();

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param SplFileInfo ...$files
     * @return array
     */
    protected static function showFileTable(OutputInterface $output, SplFileInfo ...$files): array
    {
        $fileList = [];
        $rows  = [];
        foreach ($files as $id => $file) {
            $fileInfo = new File($file->getRealPath());
            $file = array(
                'id' =>  $id,
                'filename' => $fileInfo->getBasename(),
                "filesize" => $fileInfo->getSizeFormat(),
                "createtime"  => date('Y-m-d H:i:s', $fileInfo->getCTime())
            );
            $fileList[] = $fileInfo;
            $rows[] = $file;
        }

        $table = new Table($output);
        $table->setHeaders(array('ID', '文件名', '文件大小', '创建日期'))
        ->setRows($rows);
        $table->setStyle('borderless');
        $table->render();

        return $fileList;
    }
}
