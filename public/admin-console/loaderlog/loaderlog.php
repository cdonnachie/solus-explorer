<?php
    require __DIR__ . '/../../../settings.php';    
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';

    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    $session = sec_Authenticate($cip, ui_GetUserAgent());

    //if (!$acc->authenticated) {
    if (!$session->authenticated) {
        // not authorized
        header('HTTP/1.0 401 Unauthorized', true, 401);
        header('Content-Type: application/json');
        echo '{"status":"error","error":{"code": 401,"message":"Unauthorized"}}'; 
        exit;
    }

  global $rpc;
  global $dbm;

  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["overview_precision"];
  $def_precision = (int)$settings["default_precision"];

?>
<?php
    include_once __DIR__ . '/../../includes/admin_menu.php';
?>
  <script>
    $(".menu-nav").removeClass("active");
    $("#admin-loaderlog").addClass("active");
  </script>
<section>
  <div class="row">
  <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-file-alt" aria-hidden="true"></i> Loader Log </span>
        </div>
        <div class="card-body table-responsive text-nowrap">
          
          <table id="dtMovementsList" class="table table-striped table-bordered table-hover table-sm m-0" cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                <th class="th-sm">Block Height</th>  
                <th class="th-sm">Operation</th>
                <th class="th-sm">Processing Time</th>
                <th class="th-sm">Block Hash</th>
              </tr>
            </thead>
          </table>
          
        </div>
      </div>
    </div>
  </div>
  <script type='text/javascript'> 
    // Configure Datatable
      $('#dtMovementsList').DataTable({
        "serverSide": true,
        "ordering": false,
        "searching": false,
        "pageLength": 25,
        "lengthChange": false,
        "scrollX": true,
        "pagingType": "simple_numbers",
        "processing": true,
        "ajax": {
          "url":"<?php echo $settings["explorer_address"]."/".ADMIN_PATH; ?>/data/logdp.php",
          "type":"POST",
          "data": {
            "bStart":"<?php echo null; ?>",
          },
          "dataSrc": function ( json ) {
            document.getElementById("gll_time").innerHTML = '$gll_time='+ json.processingtime_ms +' ms';
            return json.data;
          }
        },
        "language": {
            "processing": "Loading Log..."
        },
        "deferRender": true,
        "columnDefs": [ 
          {"targets": [3], "className": "text-right"},
          {"targets": "_all", "className": " text-center"}
        ]
      });
      $('div#dtMovementsList_processing.dataTables_processing').addClass('pl-3 primary-text');
      $('div#dtMovementsList_info.dataTables_info').addClass('pt-4');
  </script>

  </div>
</section>

<script type='text/javascript'>
$(document).ready(function(){
  $("#addnodeModal").on('show.bs.modal', function(event){

    if ( $.fn.dataTable.isDataTable( '#dtTimers' ) ) {
      table = $('#dtTimers').DataTable();
      table.ajax.url("<?php echo $settings["explorer_address"]."/".ADMIN_PATH; ?>/data/timersdp.php?oid="+$(event.relatedTarget).data('id')).load();
    } else {
      table = $('#dtTimers').DataTable( {
              //"serverSide": true,
        "ordering": false,
        "searching": true,
        "paging": true,
        "pageLength": 17,
        "lengthChange": false,
        //"scrollX": true,
              //"pagingType": "simple_numbers",
        "processing": true,
        "ajax": {
          "url":"<?php echo $settings["explorer_address"]."/".ADMIN_PATH; ?>/data/timersdp.php?oid="+$(event.relatedTarget).data('id'),
        },
        "language": {
          "processing": "Loading Timers..."
        },
              //"deferRender": true,
        "columnDefs": [ 
          {"targets": [0], visible: false, orderable: true},
          {"targets": [2], "className": "text-right"},
          {"targets": "_all", "className": " text-left"}
        ]
      } );
      $('div#dtTimers_processing.dataTables_processing').addClass('pl-3 primary-text');
      $('div#dtTimers_info.dataTables_info').addClass('pt-4');   
    }
    $("#logId").text($(event.relatedTarget).data('id'));
  });
});

  // Autorefresh
    // $(document).ready(function(){
    //     setTimeout(function() { 
    //         $("#content").load("loaderlog.php");
    //       }, <?php echo $settings["dashboard_refresh_sec"]; ?> * 1000);
    // });
</script>

<!-- Central Modal Small -->
<div class="modal fade" id="addnodeModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
  aria-hidden="true">

  <!-- Change class .modal-sm to change the size of the modal -->
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title w-100 primary-text" id="modalLabel">Timer Info (Id: '<span id="logId"></span>')</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-justify p-3">
        <p></p>
        <div class="table-responsive text-nowrap" > 
          <table id="dtTimers" class="table table-striped table-bordered table-hover table-sm m-0" cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">#</th>
                <th class="th-sm">Task</th>
                <th class="th-sm">Duration</th>  
                <th class="th-sm">Info</th>
              </tr>
            </thead>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Central Modal Small -->
<section class="d-none">
 <div class="row text-left mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0" id="gll_time"></p>
    <p class="small my-0 py-0" id="gti_time"></p>
   </div>
  </div>
 </div>
</section>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"admin/loaderlog",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>