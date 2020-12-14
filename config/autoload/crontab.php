<?php

use Hyperf\Crontab\Crontab;

return [
    'enable' => true,
    // 通过配置文件定义的定时任务
    'crontab' => [
        (new Crontab())->setName('FundsCollection')->setRule('* 8 * * *')->setCallback([App\Task\FundsCollection::class, 'execute'])->setMemo('解锁收益'),
    ],
];
