<?php
// 一鍵還原 SleeperService.php 到拆分前狀態
$bak = __DIR__ . '/classes/SleeperService.php.bak';
$target = __DIR__ . '/classes/SleeperService.php';
if (!is_file($bak)) {
    echo "错误: 找不到备份档案 {$bak}\n";
    echo "请执行: git checkout pre-split-v1 -- classes/SleeperService.php\n";
    exit(1);
}
copy($bak, $target);
echo "已还原 SleeperService.php\n";

// Remove trait directory
$traitDir = __DIR__ . '/classes/traits';
if (is_dir($traitDir)) {
    array_map('unlink', glob($traitDir . '/*'));
    rmdir($traitDir);
    echo "已移除 trait 目录\n";
}

echo "还原完成。也可执行:\n";
echo "  git checkout pre-split-v1 -- classes/SleeperService.php\n";
