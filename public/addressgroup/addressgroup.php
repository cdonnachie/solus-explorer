<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

  //$queries= array();
  //parse_str($_SERVER['QUERY_STRING'], $queries);
  //$address = strtr($_GET['address'],"_"," ");
  //$groupid = strtr($_GET['groupid'],"_"," ");
  $address = strtr(sanitizeInput("address","GET"),"_"," ");
  $groupid = strtr(sanitizeInput("groupid","GET"),"_"," ");
  $rtype = "";
  $gms_time = 0;

  if(!empty($address)){ 
    $noaddr = false; 
    $form_invis = ""; 
    $rtype = "address";
    $query = $address;
    $stime = milliseconds();
    $ars=db_getAddressData($address); 
    $ars_time = milliseconds() - $stime; 
    if (count($ars)>0){
      $groupid = $ars[0]->group_id;
    } else {
      //group not found
      $form_invis = "d-none";
      $notfound_visibility = "";
      $emptyrequest_visibility = "d-none";
    }
  } else {
    $ars_time = 0;
  }
  if(!empty($groupid)){ 
    $noaddr = false; 
    $form_invis = ""; 
    if($rtype != "address"){ $rtype = "groupid"; $query = $groupid;}
    $stime = milliseconds();
    $ars=db_getAddressGroupAggregates($groupid); 
    $gga_time = milliseconds() - $stime;

    //$ams = getAddressMovesData($address, 0); 
    if (count($ars)>0){
      $ar = $ars[0];
      $bal_split = splitFloat(@cdec($ar->totalBalance) < 0 ? @cdec(0) : @cdec($ar->totalBalance) , $precision, $precision);
      $addr_count = $ar->countAddresses;
      if ($settings["load_markets"]){
        $stime = milliseconds();
        $db_mm = getLatestMarketsData();
        $gmm_time = milliseconds() - $stime;
        $btc = @cdec(0); $usd = @cdec(0); $eur = @cdec(0);
        if(!empty($db_mm)){
          $btc = @cdec($bal_split->number) * @cdec($db_mm->avg_price);
          if (isset($db_mm->base_prices["USD"]) && !is_null($db_mm->base_prices["USD"])){
            $usd = $btc * @cdec($db_mm->base_prices["USD"]);
          }
          if (isset($db_mm->base_prices["EUR"]) && !is_null($db_mm->base_prices["EUR"])){
            $eur = $btc * @cdec($db_mm->base_prices["EUR"]);
          }
        }
        $btc_split = splitFloat($btc,$precision,$precision);
        $usd_split = splitFloat($usd,2,2);
        $eur_split = splitFloat($eur,2,2);
        $btc_visibile = true;
      } else {
        $btc_visibile = false;
      }
      $form_invis = "";
      $notfound_visibility = "d-none";
      $emptyrequest_visibility = "d-none";
    } else {
      //groupid not found
      $form_invis = "d-none";
      $notfound_visibility = "";
      $emptyrequest_visibility = "d-none";
    }
  } else if (!empty($address)){ 
    $form_invis = "d-none";
    $notfound_visibility = "";
    $emptyrequest_visibility = "d-none";
  } else { 
    $noaddr = true; 
    $form_invis = "d-none";
    $notfound_visibility = "d-none";
    $emptyrequest_visibility = "";
    $ars = array();
    $ams = array();
    $ar = new stdClass();
  }


?>

<!--Section: Button icon-->
<section class="<?php echo $form_invis;?>">
  <!--Grid row-->
  <div class="row">
      <!--Grid column I-->
      <div class="col-12 mb-4">
        <!--Address Card (small)-->
        <div class="card col-12 d-md-none ">
          <!--Card Data-->
            <div class="primary-text text-center py-2">
              <?php echo $groupid; ?>
            </div>
          <!--/.Card Data-->
        </div>
        <!--/.Address Card (small) Card-->

        <!--Address Header Card (big)-->
        <div class="card col-12 d-none d-md-flex">

          <!--Card Data-->
            <div class="text-left">
              <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0 mr-2"><i class="fas fa-list-ul card-icon-text" aria-hidden="true"></i></a>
              <span class="h2 h2-responsive mb-0 font-weight-bold primary-text"><?php echo $groupid; ?></span>
            </div>
          <!--/.Card Data-->

        </div>
        <!--/.Address Header Card (big)-->

      </div>
      <!--/.Grid column I-->

  </div>
  <!--Grid row-->

  <!--Grid row-->
  <div class="row">
      <!--Grid column I-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4 ">

        <!--Balance Card-->
        <div class="card col-12 text-center">
          <!--Card Data-->
          <div class="row pr-3 pl-3">
            <div class="col-md-3 col-3 text-left">
              <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0 mb-1"><i class="fas fa-coins card-icon-text" aria-hidden="true"></i></a>
            </div>

            <div class="col-md-9 col-9 text-right">
              <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $bal_split->whole."."; ?><small><?php echo $bal_split->fraction; ?></small></h1>
              <p class="font-small h4-responsive mb-0 primary-text"><?php echo "BALANCE (".$settings["coin_symbol"].")"; ?></p>
              <div class="mb-2 col-12 pt-2 pr-0 text-right ">
                <span class="font-small pr-3 h6-responsive grey-text"><?php if($btc_visibile){ echo "BTC";}; ?></span>
                <span id="value" class="font-small font-up m-0 h6-responsive font-weight-bold dark-grey-text" <?php if($btc_visibile){ echo 'data-toggle="popover-hover"';}?> ><?php if($btc_visibile){ echo $btc_split->whole .".". $btc_split->fraction;}; ?></span>
                <script>
                  $('#value').popover({
                    html: true,
                    trigger: 'hover',
                    placement: 'bottom',
                    content: function () { return '<div><div class="row"><div class="col font-small h6-responsive">BTC</div><div class="col font-small h6-responsive text-right"><b><?php echo $btc_split->whole;?>.<small><?php echo $btc_split->fraction;?></small></b></div></div><div class="row"><div class="col font-small h6-responsive">USD</div><div class="col font-small h6-responsive text-right"><b><?php echo $usd_split->whole;?>.<small><?php echo $usd_split->fraction;?></small></b></div></div><div class="row"><div class="col font-small h6-responsive">EUR</div><div class="col font-small h6-responsive text-right"><b><?php echo $eur_split->whole;?>.<small><?php echo $eur_split->fraction;?></small></b></div></div></div>'; }
                  });
                </script>

              </div>
            </div>
          </div>
          <!--/.Card Data-->

          <!--Card content-->

          <!--/.Card content-->

        </div>
        <!--/.Balance Card-->

      </div>
      <!--/.Grid column I-->


      <!--Grid column II-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4 ">

        <!--Count Card-->
        <div class="card col-12 text-center pb-2">
          <!--Card Data-->
          <div class="row pr-3 pl-3">
            <div class="col-md-3 col-3 text-left">
              <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0 mb-1"><i class="fas fa-at card-icon-text" aria-hidden="true"></i></a>
            </div>

            <div class="col-md-9 col-9 text-right">
              <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $addr_count; ?></h1>
              <p class="font-small h4-responsive mb-0 primary-text"><?php echo "GROUPED ADDRESSES"; ?></p>
              <?php 
                $gtags = '';
                $ag = db_getAddressGroupById($groupid);
                if (count($ag)>0 && isset($ag[0]->tags)){
                  echo "<div class='mb-2 col-12 pt-2 pr-0 text-right'>";
                  echo "<span class='font-small pr-3 h6-responsive grey-text'>TAGS:</span>";
                  $gtags = ui_labelBadges('', $ag[0]->tags, 'badge-primary ml-1');
                  echo $gtags."</div>";
                }
              ?>
            </div>
          </div>
          <!--/.Card Data-->

          <!--Card content-->

          <!--/.Card content-->

        </div>
        <!--/.Balance Card-->

      </div>
      <!--/.Grid column II-->
  </div>
  <!--Grid row-->
</section>
<!--/Section: Button icon-->

<?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
  <!--Section: Masternodes Table -->
  <?php 
    $stime = milliseconds();
    $gms = getAddressGroupMasternodes($rtype, $query);
    $gms_time = milliseconds() - $stime;
    $mn_enabled = 0;
    $mn_failed = 0;
    $mn_other = 0;
    $mn_total = 0;
    if ($form_invis || !isset($gms) || !isset($gms->mns)){
      $mn_invis = "d-none";
    } else {
      $mn_total = count($gms->mns);
      foreach ($gms->states as $state => $count){
        switch ($state){
          case "ENABLED":
            $mn_enabled = $count;
            break;
          case "PRE-ENABLED":
            $mn_other += $count;
            break;
          case "ACTIVE":
            $mn_other += $count;
            break;
          case "WATCHDOG_EXPIRED":
            $mn_other += $count;
            break;
          default:
            $mn_failed += $count;
        }
      }
    }
  ?>
  <section class="<?php echo $mn_invis;?>">
  <div class="row">
    <div class="col-md-12 mb-4">
      <!-- Masternodes Status Card -->
      <div class="card">
        <div class="card-header primary-color card-header-text py-2 text-left">
          <i class="fab fa-hubspot mr-1" aria-hidden="true"></i> Masternodes Status
        </div>
        <!--Card content-->
        <div class="card-body table-responsive text-nowrap" >
        <!--Sub-cards -->
        <div class="row">

          <!--Grid column I-->
          <div class="col-xl-4 col-lg-4 col-md-4 flex-col pb-2">
            <!--Msternode Number Card-->
            <div class="card col-12 text-center">
              <!--Card Data-->
              <div class="row px-3 pb-3">
                <div class="col-md-3 col-3 text-left pt-2">
                  <a type="button" class="btn-floating btn-sm success-color accent-2 ml-0 mb-1"><i class="fas fa-arrow-alt-circle-up" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $mn_enabled; ?><small><?php echo " / ".$mn_total; ?></small></h1>
                  <p class="font-small h4-responsive mb-0 primary-text"><?php echo "ENABLED"; ?></p>
                </div>
              </div>
            <!--/.Balance Card-->
            </div>
          </div>
          <!--/.Grid column I-->

          <!--Grid column II-->
          <div class="col-xl-4 col-lg-4 col-md-4 flex-col pb-2">
            <!--Msternode Number Card-->
            <div class="card col-12 text-center">
              <!--Card Data-->
              <div class="row px-3 pb-3">
                <div class="col-md-3 col-3 text-left pt-2">
                  <a type="button" class="btn-floating btn-sm danger-color accent-2 ml-0 mb-1"><i class="fas fa-arrow-alt-circle-down" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $mn_failed; ?><small><?php echo " / ".$mn_total; ?></small></h1>
                  <p class="font-small h4-responsive mb-0 primary-text"><?php echo "FAILED"; ?></p>
                </div>
              </div>
            <!--/.Balance Card-->
            </div>
          </div>
          <!--/.Grid column II-->

          <!--Grid column III-->
          <div class="col-xl-4 col-lg-4 col-md-4 flex-col pb-2">
            <!--Msternode Number Card-->
            <div class="card col-12 text-center">
              <!--Card Data-->
              <div class="row px-3 pb-3">
                <div class="col-md-3 col-3 text-left pt-2">
                  <a type="button" class="btn-floating btn-sm warning-color accent-2 ml-0 mb-1"><i class="fas fa-question-circle" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $mn_other; ?><small><?php echo " / ".$mn_total; ?></small></h1>
                  <p class="font-small h4-responsive mb-0 primary-text"><?php echo "OTHER"; ?></p>
                </div>
              </div>
            <!--/.Balance Card-->
            </div>
          </div>
          <!--/.Grid column III-->

        </div>
        <div class="row text-center pb-2">
          <div class="col-12">
            <button id="bMnTable" type="button" class="btn btn-info py-2"  data-action="show">Show Group Masternodes</button>
            <script>
              bw = $( "#bMnTable" ).width();
              $( "#bMnTable" ).click(function() {
                if ( $( this ).data( 'action' ) == 'show' ){
                  $( this ).data( 'action','hide');
                  $( this ).text('Hide Group Masternodes');
                  $('#dMnTable').removeClass('d-none');
                } else {
                  $( this ).data( 'action','show');
                  $( this ).text('Show Group Masternodes');
                  $('#dMnTable').addClass('d-none');
                }
                $( "#bMnTable" ).width(bw);
              });
            </script>
          </div>
        </div>
        <!--Sub-cards -->      
          <div id="dMnTable" class="table-container d-none">
            <table class="table table-hover table-bordered table-sm " cellspacing="0" width="100%">
              <thead>
              <?php if (isset($settings["dip2_support"]) && $settings["dip2_support"]) : ?>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Payee Address</th>
                  <th class="th-sm">Masternode ID</th>
                  <th class="th-sm">Status</th>
                  <th class="th-sm">Last Paid Block</th>
                  <th class="th-sm">Last Paid Time</th>
                  <th class="th-sm">Collateral Address</th>
                </tr>
              <?php else : ?>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Masternode ID</th>
                  <th class="th-sm">Status</th>
                  <th class="th-sm">Protocol</th>
                  <th class="th-sm">Last Seen</th>
                  <th class="th-sm">Active Time</th>
                </tr>
              <?php endif; ?>
              </thead>
              <tbody>
                <?php 
                foreach($gms->mns as $mn){
                    $key = $mn->txhash."-".$mn->outidx;
                    if (in_array($mn->status,["ENABLED"])) {
                    $badge_class="success";
                    } else if (in_array($mn->status,["ACTIVE", "PRE-ENABLED", "WATCHDOG_EXPIRED"])) {
                    $badge_class="warning";
                    } else {
                    $badge_class="danger";
                    }
                    $tier_str = "";
                    if (isset($mn->tier)){
                      if (is_numeric($mn->tier)){
                        $tier_str = "<span class='badge ml-2' style='background-color:".getChartColorArray(3)[(int)$mn->tier].";'><i class='fab fa-hubspot'></i>&nbsp;Tier ".$mn->tier."</span>";
                      } else{
                        $tier_str = "<span class='badge badge-info ml-2'><i class='fab fa-hubspot'></i>&nbsp;".$mn->tier."</span>";
                      }
                    }
                    if (isset($settings["dip2_support"]) && $settings["dip2_support"]){
                      echo "<tr><td class='text-center p-2'><a href='".$settings["explorer_address"]."/address/?address=".$mn->addr."' class='primary-text'>".$mn->addr."</a></td>"; 
                      echo "<td class='text-center p-2'>".$key.$tier_str."</td>";
                      echo "<td class='text-center p-2'><span class='badge badge-$badge_class mnlist-status h6 mb-0'>$mn->status</span></td>";
                      echo "<td class='text-center p-2'><a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$mn->lastpaidblock."'>".$mn->lastpaidblock."</a></td>";
                      echo "<td class='text-center p-2'>".date('Y-m-d H:i:s', $mn->lastpaidtime)."</td>";
                      echo "<td class='text-center p-2'><a class='primary-text mnlist-address' href='".$settings["explorer_address"]."/address/?address=$mn->colladdr'>$mn->colladdr</a></td></tr>";
                    } else {
                      echo "<tr><td class='text-center p-2'><a href='".$settings["explorer_address"]."/address/?address=".$mn->addr."' class='primary-text'>".$mn->addr."</a></td>"; 
                      //echo "<td class='text-center p-2'>".substr($key,0,8)."******".substr($key, strlen($key)-10, 10)."</td>";
                      echo "<td class='text-center p-2'>".$key.$tier_str."</td>";
                      echo "<td class='text-center p-2'><span class='badge badge-$badge_class mnlist-status h6 mb-0'>$mn->status</span></td>";
                      echo "<td class='text-center p-2'>$mn->version</td>";
                      echo "<td class='text-center p-2'>".date('Y-m-d H:i:s', $mn->lastseen)."</td>";
                      echo "<td class='text-center p-2'>".timediffToParts($mn->activetime)->string."</td><tr>";
                    }
                }
                ?>
              </tbody>
            </table>
            </div>
          </div>
          <!--/.Card content-->
      </div>
      <!--/Masternodes Status Card -->
    </div>
  </div>
  </section>
  <!--/Section: Masternodes Table -->
<?php endif; ?>

<!--Section: Addresses DataTable-->
<section class="<?php echo $form_invis;?>">
<div class="row">
  <div class="col-md-12 mb-4">
    <!--Addresses List Card-->
    <div class="card">
      <div class="card-header primary-color card-header-text py-2 text-left">
        <i class="fas fa-at mr-1" aria-hidden="true"></i> Address List
      </div>

      <!--Card content-->
      <div class="card-body table-responsive text-nowrap">
        <?php if (isset($settings["export_csv_enabled"]) && $settings["export_csv_enabled"]) : ?>
          <div class="row">
            <div class="col-12">
              <span class="float-right pb-2"><button class="btn btn-sm btn-info " id="exportToCsv">Export to CSV</button></span>
              <script type='text/javascript'>
                $('#exportToCsv').click( function(){
                  exportToCsv('<?php echo $settings["explorer_address"]; ?>/_dp/addressgroupcsv.php?groupid=<?php echo $groupid; ?>','<?php echo $groupid; ?>_addresses_<?php echo date("Y-m-d_H-i-s"); ?>.csv');
                });
              </script>
            </div>
          </div>
        <?php endif; ?> 
        <table id="dtAddressList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
          <thead>
            <tr class="table-secondary text-center">
              <th class="th-sm">Address</th>
              <th class="th-sm">Last Block</th>
              <th class="th-sm">Balance</th>
            </tr>
          </thead>
        </table>
      </div>
      <!--/.Card content-->

    </div>
    <!--/.Addresses List Card-->
  </div>      
</div>
</section>
<!--/Section: Addresses DataTable-->
<script type='text/javascript'>  
  // Configure Datatable
    $('#dtAddressList').DataTable({
      serverSide: true,
      ordering: false,
      searching: false,
      "pageLength": 25,
      "lengthChange": false,
      "scrollX": true,
      "pagingType": "full",
      "processing": true,
      "ajax": {
        "url":"../_dp/addressgroupdp.php?groupid=<?php echo $groupid;?>",
        "type": "POST",
        "dataSrc": function ( json ) {
          document.getElementById("gal_time").innerHTML = '$gal_time='+ json.processingtime_ms +' ms';
          return json.data;
        }
      },
      "language": {
			    "processing": "Loading addresses..."
			},
      "deferRender": true,
      "columnDefs": [ 
       {"targets": 2, "className": "text-right"},
       {"targets": "_all", "className": " text-center"}
      ]
    });
    //$('div#dtAddressList_processing.dataTables_length').addClass('d-none');
    $('div#dtAddressList_processing.dataTables_processing').addClass('pl-3 primary-text');
    $('div#dtAddressList_processing.dataTables_info').addClass('pt-4');
</script>

<!-- NOT FOUND SECTION -->
<section class="<?php echo $notfound_visibility;?>">
  <div class="row">
    <!-- Grid column -->
    <div class="col-12 mb-4">
      <!-- Card -->
      <div class="card card-cascade cascading-admin-card">

        <!-- Card Data -->
        <div class="row admin-up">
          <div>
            <i class="far fa-question-circle danger-color accent-2 mr-3 z-depth-2"></i>
          </div>
          <div class="data-left pl-3">
            <h5 class="text-uppercase text-danger" >address group not found</h5>
          </div>
        </div>

        <!-- Card content -->
        <div class="card-body card-body-cascade mt-2">
          <h6 > Requested address group (<?php echo $rtype;?>): <span class="font-weight-bold text-danger ml-2"><?php echo $query; ?></span></h6>
          <p class="card-text">Address group was not found in the database.<br>
          It may happen due to following reasons:
            <ul> 
              <li class="card-text">incorrect <?php echo $rtype;?> entered</li>
              <li class="card-text">address group was joined with another and thus doesn't exists anymore</li>
              <li class="card-text">explorer not fully synchronized with blockchain</li>
            </ul>
          </p>
        </div>
      </div>
      <!-- Card -->
    </div>
    <!-- Grid column -->
  </div>
</section>

<!-- EMPTY REQUEST SECTION -->
<section class="<?php echo $emptyrequest_visibility;?>">
  <div class="row">
    <!-- Grid column -->
    <div class="col-12 mb-4">
      <!-- Card -->
      <div class="card card-cascade cascading-admin-card">

        <!-- Card Data -->
        <div class="row admin-up">
          <div>
            <i class="far fa-question-circle danger-color accent-2 mr-3 z-depth-2"></i>
          </div>
          <div class="data-left pl-3">
            <h5 class="text-uppercase text-danger" >empty request</h5>
          </div>
        </div>

        <!-- Card content -->
        <div class="card-body card-body-cascade mt-2">
          <h6 >Your search request doesn't contain address or groupid.</h6>
          <p class="card-text">Navigation url is incomplete, please fix it and try again.
          </p>
        </div>
      </div>
      <!-- Card -->
    </div>
    <!-- Grid column -->
  </div>
</section>


<section class="<?php echo $form_invis;?> text-left d-none">
 <div class="row mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0">$ars_time=<?php echo $ars_time; ?> ms</p>
    <p class="small my-0 py-0">$gmm_time=<?php echo $gmm_time; ?> ms</p>
    <p class="small my-0 py-0">$gms_time=<?php echo $gms_time; ?> ms</p>
    <p class="small my-0 py-0">$gga_time=<?php echo $gga_time; ?> ms</p>
    <p class="small my-0 py-0" id="gal_time"></p>
   </div>
  </div>
 </div>
</section>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"addressgroup",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>