<?php

use Hyperf\Crontab\Crontab;

return [
    'enable' => true,
    // 通过配置文件定义的定时任务
    'crontab' => [
        (new Crontab())->setName('ETHFundsCollection')->setRule('* */8 * * *')->setCallback([App\Task\ETHFundsCollection::class, 'execute'])->setMemo('ETH归集'),
    ],
];
