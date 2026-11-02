<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/view/view_app.php';

if (!\VioletBlue\support\support_config::installed()) {
    header('Location: /install/installer.php', true, 303);
    exit;
}
\VioletBlue\view\render_app();
