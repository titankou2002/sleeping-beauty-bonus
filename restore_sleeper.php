<?php
// 一键还原 SleeperService.php 到拆分前状态
$tag = 'pre-split-v1';
$target = __DIR__ . '/classes/SleeperService.php';
echo "请执行:\n";
echo "  git checkout {$tag} -- classes/SleeperService.php\n";
echo "  git checkout {$tag} -- api.php\n\n";
echo "然后删除 trait 目录:\n";
echo "  rm -rf classes/traits\n";
echo "最后移除 traits require:\n";
echo "  git checkout {$tag} -- api.php\n";
