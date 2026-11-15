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
  $description = "What the ".ui_getCoinName()." explorer offers, who runs it, and how to get in touch. Built on Solus Explorer.";
  global $keywords;
  $keywords = strtolower($settings["title"]).", about, explorer description, contacts";
  global $title;
  $title = "About the ".ui_getCoinName()." Explorer";
  global $page_name;
  $page_name = $settings["title"]." - About";
  global $page_id;
  $page_id = "About";

  /* What this explorer offers, read from this deployment's own settings.
   * The software can do more than any one chain needs -- masternodes, Zerocoin,
   * markets -- and a visitor should see what is actually running here rather
   * than a feature list belonging to someone else's deployment. Each bullet is
   * gated on the same key that gates the page or the loader behaviour it
   * describes, so the list cannot drift away from what the menu offers. */
  $sp = isset($settings["show_pages"]) && is_array($settings["show_pages"]) ? $settings["show_pages"] : [];
  $features = [
    "A reliable orphan rollback algorithm that ensures accurate balance reporting after chain reorganisation",
    "Address grouping: automatically groups related addresses that likely belong to the same wallet, giving clearer insights into coin distribution and holders’ funds",
    "Detection of unbalanced transactions: double spend, coin injections, overspending, and other issues",
  ];
  if (!empty($settings["rvn_assets"])) {
    $features[] = "Asset tracking: issuance, supply, holders and transfers, with a page for every asset";
  }
  if (!empty($sp["mining"])) {
    $features[] = "Mining history: blocks attributed to pools, rewards, difficulty and hashrate over time";
  }
  if (!empty($sp["staking"])) {
    $features[] = "Staking activity: rewards, stake base and winner addresses";
  }
  if (!empty($sp["masternodes"]) || !empty($settings["dip2_support"])) {
    $features[] = "Full Masternode tracking including support of Dash's Deterministic Masternode List (DIP-2)";
  }
  if (!empty($settings["dip2_support"])) {
    $features[] = "Support of DASH Credit Pool (DIP-27)";
  }
  if (!empty($settings["show_zerocoin"])) {
    $features[] = "Compatibility with Zerocoin, Shield, and Cold Staking features";
  }
  if (!empty($sp["burned"])) {
    $features[] = "Burned coin accounting: burn addresses, fees and unrecoverable supply";
  }
  if (!empty($settings["load_markets"]) || !empty($sp["markets"])) {
    $features[] = "Market integration: can connect to exchange APIs to show market prices and capitalization in multiple currencies (e.g., BTC, USD, EUR)";
  }
  if (!empty($settings["export_csv_enabled"])) {
    $features[] = "CSV export of address, group and asset history";
  }
  $features[] = 'A public JSON API for wallets, services and chain tooling: <a href="'.h($settings["explorer_address"]).'/api/">see the API reference</a>';

  /* The status line is a claim about this deployment, not about the software,
   * so the operator owns the wording. The default says only what is true of
   * every copy: it carries no end date, because a date that belongs to the
   * upstream project reads to a visitor as "this site is closing". */
  $about_status = trim((string)($settings["about_status"] ?? ""));
  if ($about_status === "") {
    $about_status = "Solus Explorer is open-source software under the MIT License.";
  }

  /* Who runs this instance, as distinct from who wrote the software. With none
   * of the keys set the card is not rendered, so an existing deployment that
   * adds nothing to its settings.php looks exactly as it did before. */
  $op_name     = trim((string)($settings["operator_name"] ?? ""));
  $op_url      = trim((string)($settings["operator_url"] ?? ""));
  $op_label    = trim((string)($settings["operator_label"] ?? ""));
  if ($op_label === "") { $op_label = "Operated by"; }
  $op_about    = trim((string)($settings["about_operator"] ?? ""));
  $op_contacts = isset($settings["operator_contacts"]) && is_array($settings["operator_contacts"]) ? $settings["operator_contacts"] : [];
  $show_operator = ($op_name !== "" || $op_about !== "" || count($op_contacts) > 0);

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
                <?php foreach ($features as $feature) : ?>
                <li><?php echo $feature; ?></li>
                <?php endforeach; ?>
              </ul>
            </h6>
            <h5 class="px-2 text-justify">
            Project status
            </h5>
            <h6 class="px-2 pb-3 text-justify text-italic text-primary">
            <?php echo h($about_status); ?>
            </h6>
          </div>
        </div>
      </div>
      <!--/Grid row-->

      <?php if ($show_operator) : ?>
      <!--Grid row-->
      <div class="row">
        <!--Grid column: who runs this instance, as opposed to who wrote the software-->
        <div class="col-12 mb-4">
          <div class="card col-12 px-0">
            <div class="card-header primary-color card-header-text text-left py-2 my-0">
              <span class="h4 h4-responsive"><i class="fas fa-user-shield mr-1" aria-hidden="true"></i> Who runs this explorer</span>
            </div>

            <!--Card content-->
            <div class="card-body text-left">
              <?php if ($op_about !== "") : ?>
              <p class="default-text"><?php echo h($op_about); ?></p>
              <?php endif; ?>
              <?php if ($op_name !== "") : ?>
              <div class="row">
                <div class="flex-col col-md-4 default-category"><?php echo h($op_label); ?></div>
                <div class="flex-col col-md-8 default-text"><?php
                  if ($op_url !== "") {
                    echo '<a href="'.h($op_url).'" target="_blank" rel="noopener">'.h($op_name).'</a>';
                  } else {
                    echo h($op_name);
                  }
                ?></div>
              </div>
              <?php endif; ?>
              <?php foreach ($op_contacts as $contact) :
                      $c_label = trim((string)($contact["label"] ?? ""));
                      $c_text  = trim((string)($contact["text"] ?? ""));
                      $c_href  = trim((string)($contact["href"] ?? ""));
                      if ($c_text === "" && $c_href === "") { continue; }
                      if ($c_text === "") { $c_text = $c_href; }
              ?>
              <div class="row">
                <div class="flex-col col-md-4 default-category"><?php echo h($c_label); ?></div>
                <div class="flex-col col-md-8 default-text"><?php
                  if ($c_href !== "") {
                    echo '<a href="'.h($c_href).'" target="_blank" rel="noopener">'.h($c_text).'</a>';
                  } else {
                    echo h($c_text);
                  }
                ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <!--./Grid column-->
      </div>
      <!--./Grid row-->
      <?php endif; ?>

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
