<?php declare(strict_types=1);
namespace Compiler\Command;

use Compiler\Command\Block\Encryptor;
use Compiler\Command\Block\Os;
use Compiler\Command\Block\File;
use Compiler\Command\Block\FileException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class TestCompiler2 extends Command
{
    protected Encryptor $encryptor;

    protected function configure()
    {
        $this->setName("test:c2")
            ->setDescription("解密视频文件");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->encryptor = new Encryptor();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // $result = array_filter([$this, 'test'], fn($element) => $element >= 20);

        $files  = Os::listFile(__DIR__."/../video/", "zz", true);
        $table = new Table($output);
        $table->setHeaders(array('ID', '文件名', '字节大小', '创建日期'))
        ->setRows(static::files($files));
        $table->setStyle('borderless');
        $table->render();

        $save_video_path = __DIR__."/../video/";  

        foreach ($files as $id => $file) {
            $hd = fopen($file->getRealPath(), "r"); // 读取加密文件头
            if ($hd === false) {
                throw new FileException("视频文件读取失败.");
            }
            $header = fread($hd, 16);
            $arr = unpack("Ndata_length/Nhash_length/Nfilename_length/Nsrc_length", $header);
             // 加密文件总大小
            $bar1 = new ProgressBar($output, $arr['src_length']); // 任务总字节数大小
            $filename = fread($hd, $arr["filename_length"]);
            $filehash = fread($hd, $arr["hash_length"]);
            $data     = fread($hd, $arr["data_length"]);
            $encryptor = $this->encryptor->unlock($arr['src_length'], 
                                             $filename, $filehash, $data, "123456789");
            $output->writeln("[{$id}] {$encryptor->filename}:");
            $bar1->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%");
            $bar1->setMessage("");
            $bar1->start();
            $renameFile = $save_video_path. "/".$encryptor->filename;
            $handler = fopen( $renameFile, "w"); // 解密视频文件
            $size = fwrite($handler, $encryptor->data);
            $bar1->advance($size);
            while(!feof($hd))
            {
                 $buffer = fread($hd, 5*1024*1024);
                 $writeSize = fwrite($handler, $buffer);
                 $bar1->setMessage("<info>[解密进行中]</info>");
                 $bar1->advance($writeSize);
            }
          
            fclose($hd);
            fclose($handler);
            if ($encryptor->fileHash === md5_file($renameFile))
            {
                $bar1->setMessage("<info>[解密完成]</info>");
                $bar1->finish();
                $output->writeln("");
                continue;
            }
            $bar1->setMessage("<error>[解密失败]</error>");
            $bar1->finish();
            $output->writeln("");
        }
        return static::FAILURE;
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