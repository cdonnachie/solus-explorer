<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../lib/explorerCustom.php';
    include_once __DIR__ . '/../../lib/timeTracker.php'; 
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();
  
  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];
  $tt = new TimeTracker();
  global $tt;

  $tt->createTimer("pageTotal",0);

  $key = (array_key_exists('asset_id',$_GET)) ? sanitizeInput('asset_id',"GET") : null;

  if(!empty($key)){ 
    $noname = false; 
    $tt->createTimer('getAssetByKey', 0);
    $ast = db_getAssetByKey($key); 
    $tt->stopTimer('getAssetByKey',$key);

    if (count($ast)>0){
      $is_tag = false;
      //$is_restricted = false;
      $ar = $ast[0];
      $name = $ar->_id;
      $tt->createTimer('getAssetHoldersCountByKey', 0);
      $hcount = db_getAssetHoldersCountByKey($key); 
      $tt->stopTimer('getAssetHoldersCountByKey');      

      $is_admin = false;
      // MAIN           - Regular
      // MAIN!          - Regular Admin
      // MAIN/SUB       - Sub 
      // MAIN/SUB!      - Sub Admin
      // MAIN#UNIQUE    - Unique
      // $RESTRICTED    - Restricted
      // #TAG           - Restricted Tag

      $type = rvn_getAssetTypeByName($ar->_id);
      switch ($type){
        case "subtag":
          $desc = "asset subtag token";
          break;
        case "tag":
          $desc = "asset tag token";
          break;
        case "restricted":
          $desc = "restricted asset token";
          break;
        case "unique":
          $desc = "unique asset token";
          break;
        case "subasset_admin":
          $desc = "subasset administrative token";
          $is_admin = true;
          break;
        case "subasset":
          $desc = "subasset token";
          break;
        case "regular_admin":
          $desc = "regular asset administrative token";
          $is_admin = true;
          break;
        case "message_channel": // SIP-028
          $desc = "message channel token";
          break;
        default:
          $desc = "regular asset token";
      }



      if ($is_admin){
        $supply = fpp($ar->supply);
        $reissuable = "No";
        $units = "N/A";
        $ipfs = "N/A";
        if (isset($ar->ipfs)){
          if (isset($settings["ipfs_explorer_link"])){
            $href = strtr($settings["ipfs_explorer_link"],["!#IPFS-HASH#!"=>$ar->ipfs]);
            $ipfs = "<a href='".h($href)."' target='_blank' rel='noopener'>".h($ar->ipfs)."</a>";
          } else {
            $ipfs = h($ar->ipfs);
          }
        }
      } else {
        $supply_split = splitFloat($ar->supply, $precision, $precision);
        $supply = $supply_split->whole.".<small>".$supply_split->fraction."</small>";
        $reissuable = (isset($ar->reissuable) && $ar->reissuable>0 ) ? "Yes" : "No";
        $units = (isset($ar->units)) ? $ar->units : "Not set";
        $ipfs = "Not set";
        if (isset($ar->ipfs)){
          if (isset($settings["ipfs_explorer_link"])){
            $href = strtr($settings["ipfs_explorer_link"],["!#IPFS-HASH#!"=>$ar->ipfs]);
            $ipfs = "<a href='".h($href)."' target='_blank' rel='noopener'>".h($ar->ipfs)."</a>";
          } else {
            $ipfs = h($ar->ipfs);
          }
        }
        $akey=null;
        if (in_array($type, ["regular","subasset"])){
          $adt = db_getAssetByName($name."!", true);
          if (count($adt)>0) {
            $akey = $adt[0]->key;
          }
        }
      }

      $form_visible = true; 
      $notfound_visibile = false;
      $emptyrequest_visibile = false;

    } else {
      //asset found
      $form_visible = false; 
      $notfound_visibile = true;
      $emptyrequest_visibile = false;
    }


  } else { 
    $noname = true; 
    $form_visible = false; 
    $notfound_visibile = false;
    $emptyrequest_visibile = true;
    //$ars = array();
    //$ams = array();
    $ar = new stdClass();
  }
  
?>

<?php if ($form_visible) : ?>
  <!--Section: Button icon-->
  <section >
    <!--Grid row-->
    <div class="row">
        <!--Grid column I-->
        <div class="col-12 mb-4">
          <!--Address Card (small)-->
          <div class="card col-12 d-md-none ">
            <!--Card Data-->
              <div class="primary-text text-center pt-2">
                <?php echo $name; ?>
              </div>
            <!--/.Card Data-->
          </div>
          <!--/.Address Card (small) Card-->

          <!--Address Header Card (big)-->
          <div class="card col-12 d-none d-md-flex">
            <!--Card Data-->
            <div class="row text-left">
                <div class="pl-2 ">
                <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-2 mr-2"><i class="fas fa-box-open card-icon-text" aria-hidden="true"></i></a></div>
                <div class="p-3"><span class="h2 h2-responsive mb-0 font-weight-bold primary-text"><?php echo $name; ?></span><br>
                <span class='text-uppercase grey-text'>  <?php echo $desc; ?></span>
                </div>
              </div>
            <!--/.Card Data-->

          </div>
          <!--/.Address Header Card (big)-->

        </div>
        <!--/.Grid column I-->

    </div>
    <!--Grid row-->

    <!--Grid row-->
    <div class="row ">
      <!--Grid column I-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
        <!--Asset Parameters Card-->
        <div class="card col-12 pb-3">
            <!--Card Data-->
            <div class="row pr-3 pl-3">
              <div class="col-md-3 col-3 text-left">
                <a type="button" class="btn-floating btn-md primary-color btn-fl-color  accent-2 ml-0" ><i class="fas fa-coins card-icon-text" aria-hidden="true"></i></a>
              </div>

              <div class="col-md-9 col-9 text-right">
                <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold "><?php echo $supply; ?></h2>
                <p class="font-small h4-responsive primary-text"><?php echo "TOKEN SUPPLY"; ?></p>
              </div>
            </div>
            <div id="parContainer"> 
              <div class="row px-3 pb-3">
                <div class="table-container col-12" id="parTableContainer">
                  <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                    <tbody>
                      <tr>
                        <td class='text-left p-1'>Administrative Token </td>
                        <td class='text-right p-1 font-weight-bold'><span>
                        <?php if ($is_admin){ echo "Yes"; } else { if (empty($akey)) {echo "N/A";} else {echo "<a href='".$settings["explorer_address"]."/asset/?asset_id=".$akey."'>$name!</a>";}} ?></span></td>
                      </tr>
                      <tr>
                        <td class='text-left p-1'>Reissuable </td>
                        <td class='text-right p-1 font-weight-bold'><span> <?php echo $reissuable; ?> </span></td>
                      </tr>
                      <tr>
                        <td class='text-left p-1'>Units </td>
                        <td class='text-right p-1 font-weight-bold'><span><?php echo $units; ?></span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!--/.Masternode Count Card-->        
        </div>
      <!-- /.Grid Column I -->
      </div>

      <!--Grid column I-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
        <!--Asset Genesis Card-->
        <div class="card col-12 pb-3">
            <!--Card Data-->
            <div class="row pr-3 pl-3">
              <div class="col-md-3 col-3 text-left">
                <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-database card-icon-text" aria-hidden="true"></i></a>
              </div>

              <div class="col-md-9 col-9 text-right">
                <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold "><?php echo date('Y-m-d H:i:s', $ar->genesis_timestamp); ?></h2>
                <p class="font-small h4-responsive primary-text"><?php echo "TOKEN GENESIS"; ?></p>
              </div>
            </div>
            <div id="genContainer"> 
              <div class="row px-3 pb-3">
                <div class="table-container col-12" id="genTableContainer">
                  <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                    <tbody>
                      <tr>
                        <td class='text-left p-1'>IPFS Hash </td>
                        <td class='text-right p-1 font-weight-bold'><span> <?php echo $ipfs;?> </span></td>
                      </tr>
                      <tr>
                        <td class='text-left p-1'>Genesis TX</td>
                        <td class='text-right p-1 font-weight-bold'><span><?php echo "<a href='".$settings["explorer_address"]."/tx/?txid=".$ar->genesis_tx."'>".$ar->genesis_tx."</a>"; ?></span></td>
                      </tr>
                      <tr>
                        <td class='text-left p-1'>Holders count </td>
                        <td class='text-right p-1 font-weight-bold'><span> <?php echo $hcount; ?> </span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!--/.Masternode Count Card-->        
        </div>
      <!-- /.Grid Column I -->
      </div>

    </div>
    <!--/ Grid row-->
  </section>
  <!--/Section: Button icon-->

  <!--Section: Holders DataTable-->
  <section >
    <div class="row">
      <div class="col-md-12 mb-4">
        <!--Holders List Card-->
        <div class="card">
          <div class="card-header primary-color card-header-text  py-2">
            <span class="h4 h4-responsive"><i class="fas fa-exchange-alt mr-1" aria-hidden="true"></i> Asset Holders List</span>
          </div>

          <!--Card content-->
          <div class="card-body table-responsive text-nowrap">
            <table id="dtHoldersList" class="table table-striped table-bordered table-hover table-sm m-0" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Amount</th>
                  <th class="th-sm">Percent</th>
                </tr>
              </thead>
            </table>
          </div>
          <!--/.Card content-->

        </div>
        <!--/.Movements List Card-->
      </div>      
    </div>
  </section>
  <!--/Section: Holders DataTable-->
  <script type='text/javascript'>
    
    // Configure Datatable
      $('#dtHoldersList').DataTable({
        "serverSide": true,
        "ordering": false,
        "searching": false,
        "pageLength": 10,
        "lengthChange": false,
        "scrollX": true,
        "pagingType": "simple_numbers",
        "processing": true,
        "ajax": {
          "url":"../_dp/assetholdersdp.php?asset_id=<?php echo $key; ?>",
          "type":"POST",
          // "dataSrc": function ( json ) {
          //   document.getElementById("gah_time").innerHTML = '$gah_time='+ json.processingtime_ms +' ms';
          //   return json.data;
          // }
        },
        "language": {
            "processing": "Loading holders list..."
        },
        "deferRender": true,
        "columnDefs": [ 
        {"targets": [1,2], "className": "text-right"},
        {"targets": "_all", "className": " text-center"}
        ]
      });
      $('#dtHoldersList_length').addClass('d-none');
      $('#dtHoldersList_processing').addClass('pl-3 primary-text');
      $('#dtHoldersList_info').addClass('pt-4');

      // $('#dtHoldersList').DataTable().draw();
  </script>

  <!--Section: Movements DataTable-->
  <section >
    <div class="row">
      <div class="col-md-12">
        <!--Movements List Card-->
        <div class="card">
          <div class="card-header primary-color card-header-text  py-2">
            <span class="h4 h4-responsive"><i class="fas fa-exchange-alt mr-1" aria-hidden="true"></i> Asset Movements List</span>
          </div>

          <!--Card content-->
          <div class="card-body table-responsive text-nowrap">
        <?php if (isset($settings["export_csv_enabled"]) && $settings["export_csv_enabled"]) : ?>
          <div class="row">
            <div class="col-12">
              <span class="float-right pb-2"><button class="btn btn-sm btn-info " id="exportToCsv">Export to CSV</button></span>
              <script type='text/javascript'>
                $('#exportToCsv').click( function(){
                  exportToCsv('<?php echo $settings["explorer_address"]; ?>/_dp/assetmovescsv.php?asset_id=<?php echo $key; ?>','<?php echo $name; ?>_movements_<?php echo date("Y-m-d_H-i-s"); ?>.csv');
                });
              </script>
            </div>
          </div>
        <?php endif; ?>             
            <table id="dtMovementsList" class="table table-striped table-bordered table-hover table-sm m-0" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Transaction Hash</th>
                  <th class="th-sm">Confirmations</th>
                  <th class="th-sm">Amount</th>
                </tr>
              </thead>
            </table>
          </div>
          <!--/.Card content-->

        </div>
        <!--/.Movements List Card-->
      </div>      
    </div>
  </section>
  <!--/Section: Movements DataTable-->
  <script type='text/javascript'>
    
    // Configure Datatable
      $('#dtMovementsList').DataTable({
        "serverSide": true,
        "ordering": false,
        "searching": false,
        "pageLength": 20,
        "lengthChange": false,
        "scrollX": true,
        "pagingType": "simple_numbers",
        "processing": true,
        "ajax": {
          "url":"../_dp/assetmovesdp.php?asset_id=<?php echo $key; ?>",
          "type":"POST"
        },
        "language": {
            "processing": "Loading movements list..."
        },
        "deferRender": true,
        "columnDefs": [ 
        {"targets": 4, "className": "text-right"},
        {"targets": "_all", "className": "text-center"}
        ]
      });
      $('#dtMovementsList_length').addClass('d-none');
      $('#dtMovementsList_processing').addClass('pl-3 primary-text');
      $('#dtMovementsList_info').addClass('pt-4');
  </script>
<?php endif; ?>

<?php if ($notfound_visibile) : ?>
<!-- NOT FOUND SECTION -->
  <section >
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
              <h5 class="text-uppercase text-danger" >asset not found</h5>
            </div>
          </div>

          <!-- Card content -->
          <div class="card-body card-body-cascade mt-2">
            <h6 > Requested asset id: <span class="font-weight-bold text-danger ml-2"><?php echo $key; ?></span></h6>
            <p class="card-text">Asset was not found in the database.<br>
            It may happen due to following reasons:
              <ul> 
                <li class="card-text">incorrect asset id entered</li>
                <li class="card-text">asset was not yet issued</li>
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
<?php endif; ?>

<?php if ($emptyrequest_visibile) : ?>
<!-- EMPTY REQUEST SECTION -->
  <section >
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
            <h6 >Your search request doesn't contain asset name.</h6>
            <p class="card-text">Navigation url is incomplete, please fix it and try again.
            </p>
          </div>
        </div>
        <!-- Card -->
      </div>
      <!-- Grid column -->
    </div>
  </section>
<?php endif; ?>
<?php 
  $tt->stopTimer("pageTotal");
?>

<section class="d-none">
 <div class="row mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0" id="gah_time"></p>
    <p class="small my-0 py-0" id="gam_time"></p>
   </div>
  </div>
 </div>
</section>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"asset",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>