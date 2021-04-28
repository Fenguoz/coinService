<?php

use Hyperf\Crontab\Crontab;

return [
    'enable' => true,
    // 通过配置文件定义的定时任务
    'crontab' => [
        // (new Crontab())->setName('ETHFundsCollection')->setRule('* 0,8,16 * * *')->setCallback([App\Task\ETHFundsCollection::class, 'execute'])->setMemo('ETH归集'),
        (new Crontab())->setName('TRONFundsCollection')->setRule('0 0,4,8,12,16,20 * * *')->setCallback([App\Task\TRONFundsCollection::class, 'execute'])->setMemo('TRON归集'),
    ],
];
