<?php
declare(strict_types=1);
$uid = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);
$target = $uid !== false && $uid !== null && $uid > 0 ? '/admin/songs/' . $uid : '/';
header('Location: /#' . $target, true, 303);
exit;
