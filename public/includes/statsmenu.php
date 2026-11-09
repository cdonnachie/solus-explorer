<?php
    require __DIR__ . '/../../settings.php';
    global $settings;
?>
<nav class="navbar navbar-expand-lg navbar-dark primary-color my-2">
  <span class="navbar-brand" >Statistics</span>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item text-uppercase menu-nav" id="stats-general">
        <a class="nav-link" href="#general">General</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="stats-coinnomics">
        <a class="nav-link" href="#">Coinnomics</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="stats-loaderlog">
        <a class="nav-link" href="#asset">Assets</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="stats-pow">
        <a class="nav-link" href="#pow">Proof-of-Work</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="stats-pos">
        <a class="nav-link" href="#pos">Proof-of-Stake</a>
      </li>
      <li class="nav-item text-uppercase menu-nav" id="stats-pos">
        <a class="nav-link" href="#mn">Masternode</a>
      </li>
    </ul>
    <?php '<!-- <form class="form-inline">
      <div class="md-form my-0">
        <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
      </div> 
    </form> --> '?>
  </div>
</nav>
