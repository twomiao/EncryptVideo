<?php declare(strict_types=1);
namespace Compiler\Command;

use Compiler\Command\Block\Encryptor;
use Compiler\Command\Block\Os;
use Compiler\Command\Block\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class TestCompiler extends Command
{
    protected function configure()
    {
        $this->setName("test:c")
            ->setDescription("加密视频文件");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // $result = array_filter([$this, 'test'], fn($element) => $element >= 20);
        $files  = Os::listFile(__DIR__, true);

        $rows = [];
        foreach($files as $id => $file)
        {
            $rows[] = array(
                $id, $file->getFilename(), $file->getSize(), 
                date('Y-m-d H:i:s', $file->getCTime())   
            );
        }

        $table = new Table($output);
        $table
        ->setHeaders(array('ID', '文件名', '字节大小', '创建日期'))
        ->setRows($rows);
        $table->setStyle('borderless');
        $table->render();

        
        $output = new ConsoleOutput();

        $bar1 = new ProgressBar($output, 100);
        $bar2 = new ProgressBar($output, 100);
        $bar3 = new ProgressBar($output, 100);
        $bar4 = new ProgressBar($output, 100);
        $bar1->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%');
        $bar1->setMessage("");
        $bar1->start();
        print "\n";
        $bar2->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%');
        $bar2->setMessage("");
        $bar2->start();
        print "\n";
        $bar3->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%');
        $bar3->setMessage("");
        $bar3->start();
        print "\n";
        $bar4->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%');
        $bar4->setMessage("");
        $bar4->start();

        for ($i = 1; $i <= 100; $i++) {
            $output->write("\33[3A");
            // up one line
            usleep(1000);
            $bar1->setMessage("<info>Task Dwonloading</info>");
            if ($i===100)
            {
                $bar1->setMessage("<info>[Task success]</info>");
            }
            $bar1->advance();
            print "\n";
            usleep(1000);

            $bar2->setMessage("<info>Task Dwonloading</info>");
            if ($i===100)
            {
                $bar2->setMessage("<info>[Task success]</info>");
            }
            $bar2->advance();
            
            print "\n";
            usleep(1000);
            $bar3->setMessage("<info>Task Dwonloading</info>");
            if ($i===100)
            {
                $bar3->setMessage("<info>[Task success]</info>");
            }
            $bar3->advance();
            
            print "\n";
            usleep(1000);
            $bar4->setMessage("<info>Task Dwonloading</info>");
            if ($i===100)
            {
                $bar4->setMessage("<info>[Task success]</info>");
            }
            $bar4->advance();   
        }
        $bar1->finish();
        $bar2->finish();
        $bar3->finish();
        $bar4->finish();
        print "\n";
        return static::SUCCESS;

        // $encryptor = new Encryptor();
        //  foreach ($files as $file) {
        //     $newFile = new File($file->getRealPath());
        //     $newFile->setNewPassword("123456789");
        //     $encryptor->addFile($newFile);
        //     $fileheader = $encryptor->locked($newFile); // 文件头部加密
        //     // var_dump($data);
        //     $encryptor->unlock($fileheader, "123456789"); // 解密
        //  }
        return static::SUCCESS;
    }
}