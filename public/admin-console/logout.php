<?php
  require_once __DIR__ . '/../../lib/common.php';
  include_once __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerSec.php';
  include_once __DIR__ . '/../../lib/explorerDb.php';
  include_once __DIR__ . '/../../lib/explorerUi.php';

  sec_InvalidateCookie();
  header('Location: ' . $settings["explorer_address"] . '/' . ADMIN_PATH . '/', true, 303);
?>
