<?php
declare(strict_types=1);
$type = $_GET['type'] ?? 'face';
$type = is_string($type) && in_array($type, ['face', 'favicon', 'background', 'ifont'], true) ? $type : 'face';
header('Location: /#/admin/upload/' . $type, true, 303);
exit;
