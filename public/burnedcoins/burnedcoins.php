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
  $burned_amount = @cdec(0);
  $burned_recoverable = @cdec(0);
  $burned_unrecoverable = @cdec(0);

  $addresses = array();

  $burn_addresses = $settings["burn_addresses"];
  if (count($burn_addresses)>0){
    foreach ($burn_addresses as $addr){
      $addr_data = db_getAddressData($addr);
      if (count($addr_data)>0){
        $addr_data = $addr_data[0];
        $tags = db_getAddressTags($addr);
        $burner = new StdClass();
        $burner->address  = $addr;
        $burner->group_id = $addr_data->group_id;
        $burner->received = @cdec($addr_data->received);
        $burner->sent     = @cdec($addr_data->sent);
        $burner->last_block = $addr_data->last_block;
        $burner->alias = $tags->address_alias;
        $burner->tags = $tags->group_tags;

        $burned_amount    += $burner->received;
        if ($addr != "Fee Collector") {
          $burned_unrecoverable += $burner->received;
        } else {
          $burned_recoverable += $burner->received;
        }
        array_push($addresses,$burner);
      }
    }
  }
  $burner_table = array();
  foreach ($addresses as $burner){
    $m = array();
    $tags_str = "";
    $alias_str = "";
    $recoverable = "";
    //if ($burner->address == "Fee Collector") {$recoverable = "&nbsp;<span class='badge badge-primary ml-1 mb-1'>R</span>";}
    $alias_str = ui_labelBadge($burner->alias, 'badge-info ml-1 mb-1');
    $tags_str  = ui_labelBadges('', $burner->tags, 'badge-primary ml-1 mb-1');

    $received_split = splitFloat($burner->received,$precision,$precision);
    $sent_split     = splitFloat($burner->sent,$precision,$precision);

    // Address
    array_push($m,"<a class='blocklist-hash primary-text ' href='".$settings["explorer_address"]."/address/?address=".$burner->address."'>" .$burner->address."</a>".$alias_str.$recoverable);
    // Group
    array_push($m,"<a class='blocklist-hash primary-text ' href='".$settings["explorer_address"]."/addressgroup/?groupid=".$burner->group_id."'>" .$burner->group_id."</a>".$tags_str);
    // Last Block
    array_push($m,"<a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$burner->last_block."'>".$burner->last_block."</a>");

    // Received amount
    array_push($m,"<span class='blocklist-amount '>".$received_split->whole."<small>.".$received_split->fraction."</small></span>");
    // Received amount
    array_push($m,"<span class='blocklist-amount '>".$sent_split->whole."<small>.".$sent_split->fraction."</small></span>");

    array_push($burner_table, $m);
  }

  $unrecoverable_split = splitFloat($burned_unrecoverable,$precision,$precision);
  $recoverable_split = splitFloat($burned_recoverable,$precision,$precision);
?>
<section>
  <!--Grid row-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-xl-6 col-lg-6 col-md-12 ">
      <div class="card mb-4">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-recycle card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold "><?php echo $recoverable_split->whole."."; ?><small><?php echo $recoverable_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text">TOTAL FEES</p>
          </div>
        </div>
        <!--/.Card Data-->

        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small py-1 h6-responsive grey-text">Coins spent as fees are deducted from the calculated coin supply and usually claimed by the miner from the coinbase.</span>
            
          </div>
        </div>
        <!--/.Card content-->
      </div>
    </div>

    <!--Grid column II-->
    <div class="col-xl-6 col-lg-6 col-md-12 ">
      <div class="card mb-4">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-fire-alt card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold "><?php echo $unrecoverable_split->whole."."; ?><small><?php echo $unrecoverable_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text">UNRECOVERABLY BURNED</p>
          </div>
        </div>
        <!--/.Card Data-->

        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small py-1 h6-responsive grey-text">Unrecoverably burned coins are not deducted from the calculated coin supply and not compensated from the coinbase.</span>
          </div>
        </div>
        <!--/.Card content-->
      </div>
    </div>
  </div>

</section>

<section>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header card-header primary-color card-header-text py-2 text-left">
          <span class="h4 h4-responsive"><i class="fas fa-list-ul mr-1" aria-hidden="true"></i> Burn Address List</span>
        </div>

        <div class="card-body table-responsive text-nowrap">
          <table id="dtBurnerList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Address</th>
                <th class="th-sm">Address Group ID</th>
                <th class="th-sm">Last Block</th>
                <th class="th-sm">Received</th>
                <th class="th-sm">Sent</th>
              </tr>
            </thead>
          </table>
          <!--/Section: Masternodes DataTable-->
          <script type='text/javascript'>
            var dataSet = <?php echo json_encode($burner_table);?>

            // Configure Datatable
              $('#dtBurnerList').DataTable({
                "pageLength": 25,
                "scrollX": true,
                "pagingType": "simple_numbers",
                //"stateSave": true,
                "ajax": function (data, callback, settings) {
                  callback( { data: dataSet } );
                },
                "deferRender": true,
                "order": [ [3, "desc"] ],
                "columnDefs": [ 
                  {"targets": [3,4], "className": "text-right"},
                  {"targets": "_all", "className": "text-center"}
                ]
              });
              $('.dataTables_length').addClass('bs-select');
              $('.dataTables_info').addClass('pt-4');
          </script>
        </div>

      </div>
    </div>      
  </div>
</section>


<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"burnedcoins",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>