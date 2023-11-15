<?php
declare(strict_types=1);

namespace Compiler\encrypt\Commands;

use Compiler\encrypt\Chunk;
use Compiler\encrypt\File;
use Compiler\Encrypt\Utils;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FastDecrypt extends Command
{
    private OutputInterface $output;
    protected function configure()
    {
        $this->setName("fast:decrypt")
            ->setDescription("快速解密视频文件");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        $this->output = $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = Utils::scanFiles(__DIR__ . "/../../videos", "zz");

        if (empty($files))
        {
            $output->writeln('<bg=yellow;options=bold>未发现任何加密文件!</>');
            return self::SUCCESS;
        }

        $files = self::showFileTable($output, ...$files);

        $password = '995200452p';

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
                $origin = new \Compiler\encrypt\EncryptFile($file->getRealPath());
                // 加密信息
                $encryptedInfo = $origin->getEncryptInfo($password);
                if (!$encryptedInfo)
                {
                    $output->writeln("<error>「校验失败」</error>");
                    continue;
                }
                $filename = $encryptedInfo['filename'];
                $originFile = $origin->getPath().DIRECTORY_SEPARATOR.$filename;
                $origin->setOriginFilename($originFile);
                $this->output->writeln("<question>「{$filename}」：</question>");
                // 目标文件
                $targetFile = new File($originFile);

                // 开始加密
                $this->fastDecrypt($origin, $targetFile, $encryptedInfo, $password);
            } catch (\Exception $e) {
                $output->writeln("<bg=red;options=bold>{$e->getMessage()}</>");
            }
            $output->writeln("");
        }
        return self::SUCCESS;
    }

    /**
     * @param File $origin
     * @param File $target
     * @param array $encryptedInfo
     * @param string $password
     * @return void
     * @throws \Exception
     */
    protected function fastDecrypt(File $origin, File $target, array $encryptedInfo, string $password)  : void {
        // 创建进度条
        $progressBar = new ProgressBar($this->output, $origin->getSize());
        $progressBar->setMessage("<info>「开始解密」</info>");
        $progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%");
        /**
         * @var $chunk Chunk
         */
        foreach ($encryptedInfo['info'] as $chunk)
        {
            $data = $origin->read($chunk->length);
            $progressBar->advance(strlen($data));
            if ($chunk instanceof Chunk && $chunk->isEncrypted )
            {
                $data = Utils::decryptString($data, $password);
            }
            $target->write($data);
            $progressBar->display();
        }
        $progressBar->setMessage("<info>「校验文件中」</info>");
        $progressBar->display();
        if (!hash_equals($encryptedInfo['hash'], md5_file($origin->getOriginFilename(), true)))
        {
            $progressBar->setMessage("<error>「校验失败」</error>");
            $progressBar->display();
            return;
        }

        $progressBar->setMessage("<info>「解密完成」</info>");
        $progressBar->finish();
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
        $table->setHeaders(array('ID', '加密文件', '文件大小', '创建日期'))
        ->setRows($rows);
        $table->setStyle('borderless');
        $table->render();

        return $fileList;
    }
}
