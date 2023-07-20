<?php declare(strict_types=1);

use Compiler\Command\Block\AesUtils;
use Compiler\Command\Block\File;

require __DIR__ . '/vendor/autoload.php';

date_default_timezone_set("Asia/Shanghai");

use Compiler\Command\TestCompiler;
use Compiler\Command\TestCompiler2;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new TestCompiler());
$application->add(new TestCompiler2());
$application->run();