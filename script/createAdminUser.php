<?php 

require '../settings.php';
global $settings;
require '../lib/explorerDb.php';
require '../lib/explorerUi.php';
require '../lib/explorerSec.php';
global $dbm;

print("This script will create global admin user, if user exists it will be updated\n\n");
exit(sec_promptCreateAdminUser());

?>