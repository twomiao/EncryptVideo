<?php declare(strict_types=1);
namespace Compiler\Command;

use Compiler\Command\Block\Encryptor;
use Compiler\Command\Block\Os;
use Compiler\Command\Block\File;
use Compiler\Command\Block\FileException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCompiler extends Command
{
    protected Encryptor $encryptor;

    protected function configure()
    {
        $this->setName("test:c")
            ->setDescription("加密视频文件");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->encryptor = new Encryptor();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // $result = array_filter([$this, 'test'], fn($element) => $element >= 20);
        
        $files  = Os::listFile(__DIR__."/../video/", "mp4", true);
        $table = new Table($output);
        $table->setHeaders(array('ID', '文件名', '字节大小', '创建日期'))
        ->setRows(static::files($files));
        $table->setStyle('borderless');
        $table->render();

        $save_video = __DIR__."/../video/";
        $suffix = ".zz";

        foreach ($files as $id => $file) {
            $hd = fopen($file->getRealPath(), "r");
            if ($hd === false) {
                throw new FileException("视频文件读取失败.");
            }
            fseek($hd, Encryptor::ENCRYPT_BINARY_LENGTH);
            $encrypteFile = $save_video . "/". $file->randomFileName();
            $handler = fopen($encrypteFile, "w");
            if ($handler === false) {
                throw new FileException("加密文件创建失败.");
            }
            $bar1 = new ProgressBar($output, $file->getSize());  
            $output->writeln("[{$id}] {$file->getFilename()}:");
            $bar1->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%");
            $bar1->setMessage("");
            $bar1->start();
            $encryptFile = new File($file->getRealPath());
            $encryptFile->setNewPassword("123456789");
            $this->encryptor->addFile($encryptFile);
            $fileheader = $this->encryptor->locked($encryptFile); // 文件头部加密
            fwrite($handler, $fileheader); // 写入文件头部
            $bar1->advance(Encryptor::ENCRYPT_BINARY_LENGTH);

            while(!\feof($hd))
            {
                $buffer = fread($hd, 1024*1024*5); // 每次读取5MB数据写入目标文件
                $size = fwrite($handler, $buffer);
                $bar1->setMessage("<info>[加密进行中]</info>");
                $bar1->advance($size);
            }
            \fclose($hd);
            \fclose($handler);
            $bar1->setMessage("<info>[加密完成]</info>");
            $bar1->finish();
            $output->writeln("");
        }
        return static::SUCCESS;
    }

    protected static function files(array $files) : array {
        $fileList = [];
        foreach($files as $id => $file)
        {
            $fileList[] = array(
                $id, $file->getFilename(), $file->getSize(), 
                date('Y-m-d H:i:s', $file->getAtime())   
            );
        }
        return $fileList;
    }
}