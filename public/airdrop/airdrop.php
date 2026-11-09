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

  $mn_str = "";
  $mn = null;
  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

if ( (isset($settings["show_pages"]["airdrop"]) && $settings["show_pages"]["airdrop"]) || (isset($mn) && !empty($mn) && isset($settings["magic_key"]) && $mn == $settings["magic_key"])) {
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

  $totals = db_getAirdropTotals();
  //var_dump($totals);
  
  $total_split = splitFloat($totals->total,$precision,$precision);
  $claimed_split = splitFloat($totals->claimed,$precision,$precision);
  //var_dump((string)$totals->total)

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
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-cloud-rain card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold "><?php echo $total_split->whole."."; ?><small><?php echo $total_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text">TOTAL AMOUNT</p>
          </div>
        </div>
        <!--/.Card Data-->

        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small py-1 h6-responsive grey-text">Total amount of all airdriop options.</span>
            
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
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-hand-holding-water card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold "><?php echo $claimed_split->whole."."; ?><small><?php echo $claimed_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text">CLAIMED AMOUNT</p>
          </div>
        </div>
        <!--/.Card Data-->

        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small py-1 h6-responsive grey-text">Total amount of options claimed by the address owners.</span>
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
          <span class="h4 h4-responsive"><i class="fas fa-list-ul mr-1" aria-hidden="true"></i> Airdrop Options</span>
        </div>

        <div class="card-body table-responsive text-nowrap">
          <table id="dtAirdropList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Address</th>
                <th class="th-sm">Amount</th>
                <th class="th-sm">Status</th>
              </tr>
            </thead>
          </table>
          <!--/Section: Masternodes DataTable-->
          <script type='text/javascript'>
            $('#dtAirdropList').DataTable({
              //serverSide: true,
              ordering: true,
              searching: true,
              "pageLength": 50,
              "scrollX": true,
              "pagingType": "full",
              //"processing": true,
              "ajax": {
                "url":"../_dp/airdropsdp.php<?php echo $mn_str; ?>",
                "type": "GET",
                "dataSrc": function ( json ) {
                  return json.data;
                }
              },
              //"language": {
              //    "processing": "Loading airdrop options..."
              //},
              "deferRender": true,
              "columnDefs": [ 
                {"targets": 1, "className": "text-right"},
                {"targets": "_all", "className": " text-center"},
                {"targets":[1],"render": function (data,type){
                  if ( type === "sort" || type === 'type' ) {
                      return data;
                  } else {
                    sarr = data.split(".");
                    if (sarr.length == 2){
                      return "<span class='txlist-amount'>"+sarr[0]+"<small>."+sarr[1]+"</small></span>";
                    }
                  }
                }},
              ],
              order:[[1,"desc"]],
            });
            $('.dataTables_length').addClass('d-none');
            $('.dataTables_processing').addClass('pl-3 primary-text');
            $('.dataTables_info').addClass('pt-4');
          </script>
        </div>

      </div>
    </div>      
  </div>
</section>


<?php 

    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"airdrop",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
  } else {
    header("Location: /err/404/");
  }
?>