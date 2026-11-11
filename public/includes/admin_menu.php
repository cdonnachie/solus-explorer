<?php
    require __DIR__ . '/../../settings.php';
    global $settings;
?>
<nav class="navbar navbar-expand-lg navbar-dark primary-color my-2">
  <span class="navbar-brand" >Administration</span>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item text-uppercase menu-nav" id="admin-dashboard">
        <a class="nav-link" href="<?php echo $settings["explorer_address"]."/".ADMIN_PATH;?>/dashboard/">Dashboard</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="admin-usestats">
        <a class="nav-link" href="<?php echo $settings["explorer_address"]."/".ADMIN_PATH;?>/usestats/">Usage Stats</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="admin-loaderlog">
        <a class="nav-link" href="<?php echo $settings["explorer_address"]."/".ADMIN_PATH;?>/loaderlog/">Loader Log</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="admin-console">
        <a class="nav-link" href="<?php echo $settings["explorer_address"]."/".ADMIN_PATH;?>/console/">Console</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="admin-labels">
        <a class="nav-link" href="<?php echo $settings["explorer_address"]."/".ADMIN_PATH;?>/labels/">Labels</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="admin-logout">
        <a class="nav-link" href="<?php echo $settings["explorer_address"]."/".ADMIN_PATH;?>/logout.php">Log out</a>
      </li>

    </ul>
  </div>
</nav>
