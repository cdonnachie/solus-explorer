<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php';
  include_once __DIR__ . '/../../lib/timeTracker.php';

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  $apt = new TimeTracker;
  $apt->createTimer("total",0);
  $cip = ui_GetUserIpAddress();

  global $description;
  $description = "What the ".ui_getCoinName()." explorer offers: address grouping, stats, markets, and API. Built on Solus Explorer.";
  global $keywords;
  $keywords = strtolower($settings["title"]).", about, explorer description, contacts";
  global $title;
  $title = "About the ".ui_getCoinName()." Explorer";
  global $page_name;
  $page_name = $settings["title"]." - About";
  global $page_id;
  $page_id = "About";

  include_once __DIR__ . '/../includes/header.php';
  
?>

  <!--Main layout-->
  <main>

    <div id="content" class="container-fluid text-center">
    
      <!--Section: -->
      <section>
      <!--Grid row-->
      <div class="row">
        <div class="col-12 mb-4">
          <div class="card col-12 p-4">
            <h2 class="pb-4 "> About Solus Explorer </h2>
            <h6 class="px-2 pb-3 text-justify">
            Solus Explorer is a blockchain explorer developed from scratch by the Cryptoscope team.
            It is fully compatible with Bitcoin Core and its forks, and currently supports both Proof-of-Work (PoW) and Proof-of-Stake (PoS) consensus algorithms.
            </h6>
            <h5 class="px-2 text-justify">
            Key features
            </h5>
            <h6 class="px-2 pb-3 text-justify">
              <ul>
                <li>A reliable orphan rollback algorithm that ensures accurate balance reporting after chain reorganisation</li>
                <li>Address grouping: automatically groups related addresses that likely belong to the same wallet, giving clearer insights into coin distribution and holders’ funds</li>
                <li>Compatibility with Zerocoin, Shield, and Cold Staking features</li>
                <li>Full Masternode tracking including support of Dash's Deterministic Masternode List (DIP-2)</li>
                <li>Support of DASH Credit Pool (DIP-27) </li>
                <li>Detection of unbalanced transactions: double spend, coin injections, overspending, and other issues</li>
                <li>Market integration: can connect to exchange APIs to show market prices and capitalization in multiple currencies (e.g., BTC, USD, EUR)</li>
              </ul>
            </h6>
            <h5 class="px-2 text-justify">
            Project status
            </h5>
            <h6 class="px-2 pb-3 text-justify text-italic text-primary">
            Solus Explorer is open-source software under the MIT License. Its code base is supported on a best-effort basis from December 2026, and support ends no later than 31 January 2027.
            </h6>
          </div>
        </div>
      </div>
      <!--/Grid row-->

      <!--Grid row-->
      <div class="row">
        <!--Grid column I-->
        <div class="col-lg-6 col-md-12 flex-col mb-4">
          <div class="card col-12 px-0">
            <div class="card-header primary-color card-header-text text-left py-2 my-0">
              <span class="h4 h4-responsive"><i class="fas fa-file-code mr-1" aria-hidden="true"></i> Development</span>
            </div>

            <!--Card content-->
            <div class="card-body text-nowrap text-left">
              <div class="row">
                <div class="flex-col col-md-4 default-category">Design and coding</div>
                <div class="flex-col col-md-8 default-text">unclear#0122, Cryptoscope.io</div>
              </div>
            </div>
          </div>
        </div>
        <!--./Grid column I-->

        <!--Grid column II-->
        <div class="col-lg-6 col-md-12 flex-col mb-4">
          <div class="card col-12 px-0">
            <div class="card-header primary-color card-header-text text-left py-2 my-0">
              <span class="h4 h4-responsive"><i class="fas fa-hands-helping mr-1" aria-hidden="true"></i> Contributions / 3rd Parties </span>
            </div>

            <!--Card content-->
            <div class="card-body text-nowrap text-left">
              <div class="row">
                <div class="flex-col col-md-4 default-category">Websocket PHP</div>
                <div class="flex-col col-md-8 default-text">Textalk, Ignas Bernotas</div>
              </div>
              <div class="row">
                <div class="flex-col col-md-4 default-category text-center">Style Set</div>
                <div class="flex-col col-md-8 default-text"><a href="https://getbootstrap.com" target="_blank">Bootstrap 4</a></div>
              </div>
            </div>
          </div>
        </div>
        <!--./Grid column II-->  
      </div>
      <!--./Grid row-->

      <?php if (false) : ?>
      <!--Grid row-->
      <div class="row">
        <!--Grid column I-->
        <div class="col-12 mb-4">
          <div class="card col-12 px-0">
            <div class=" card-header primary-color card-header-text text-left py-2 my-0">
              <span class="h4 h4-responsive"><i class="fas fa-cogs mr-1" aria-hidden="true"></i> Hosting and Platform</span>
            </div>
            <div class="row">
              <div class="col-lg-6 col-md-12 flex-col">
                <!--Card content-->
                <div class="card-body text-nowrap text-left">
                  <div class="row">
                    <div class="flex-col col-md-8 default-category text-center">Server Hosting</div>
                    <div class="flex-col col-md-4 default-text"><a href="<?php echo $settings["hosting_ref"]["href"]; ?>" target="_blank"><?php echo $settings["hosting_ref"]["name"]; ?></a></div>
                  </div>
                  <div class="row">
                    <div class="flex-col col-md-8 default-category text-center">Operating System</div>
                    <?php 
                      $os = getOsVersion();
                      if ($os['id'] == 'centos') {
                        $os_name = $os['pretty_name'];
                        $href = "https://www.centos.org/";
                      } else if ($os['id'] == 'ubuntu') {
                        $os_name = $os['pretty_name'];
                        $href = "https://www.ubuntu.com/";
                      } else {
                        $os_name = $os['pretty_name'];
                        $href = "#";
                      }
                    ?>
                    <div class="flex-col col-md-4 default-text"><a href="<?php echo $href; ?>" target="_blank"><?php echo $os_name; ?></a></div>
                  </div>
                  <div class="row">
                    <?php 
                      $db_href = "https://www.mongodb.com/";
                      $db_text = "mongoDB ". db_getDbVersion();
                    ?>
                    <div class="flex-col col-md-8 default-category text-center">Database Server</div>
                    <div class="flex-col col-md-4 default-text"><a href="<?php echo $db_href; ?>" target="_blank"><?php echo $db_text; ?></a></div>
                  </div>
                </div>
              </div>
              <div class="col-lg-6 col-md-12 flex-col">
                <!--Card content-->
                <div class="card-body text-nowrap text-left">
                  <div class="row">
                    <div class="flex-col col-md-8 default-category text-center">Web Server</div>
                    <div class="flex-col col-md-4 default-text"><a href="https://apache.org/" target="_blank">Apache</a></div>
                  </div>
                  <div class="row">
                    <div class="flex-col col-md-8 default-category text-center">Scripting Language</div>
                    <div class="flex-col col-md-4 default-text"><a href="https://www.php.net" target="_blank">PHP</a></div>
                  </div>
                  <div class="row">
                    <div class="flex-col col-md-8 default-category text-center">Style Set</div>
                    <div class="flex-col col-md-4 default-text"><a href="https://getbootstrap.com" target="_blank">Bootstrap 4</a></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!--./Grid column III-->
      </div>
      <!--Grid row-->
      <?php endif; ?>
      </section>
      <!--/Section: -->

    </div>
</main>
<!--/Main layout-->

<?php
  include_once __DIR__ . '/../includes/footer.php';
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"about",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
