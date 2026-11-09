<?php
    require __DIR__ . '/../../../settings.php';    
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';

    $session = sec_Authenticate(ui_GetUserIpAddress(), ui_GetUserAgent());

    if (!$session->authenticated) :
  ?>
    <script type='text/javascript'>
       window.location.replace("<?php echo $settings["explorer_address"]."/".ADMIN_PATH; ?>/");
    </script>
<?php else :
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    global $rpc;
    global $dbm;

    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["overview_precision"];
    $def_precision = (int)$settings["default_precision"];

    include_once __DIR__ . '/../../includes/admin_menu.php';
?>
  <script>
    $(".menu-nav").removeClass("active");
    $("#admin-console").addClass("active");
  </script>
    <h5 class="pt-4 pb-0"> Execute CLI command </h5>
    <section>
        <div class="row">
            <div class="col-12 pb-3">
                <form class="form">
                    <div class="row py-2">
                        <div class="col col-12">
                            <!-- Default input -->
                            <label class="sr-only" for="inlineCommand">Command</label>
                            <div class="input-group ">
                                <div class="input-group-prepend">
                                <div class="input-group-text">coin-cli</div>
                                </div>
                                <input type="text" class="form-control py-0" style="width:40em;" id="inlineCommand" placeholder="command" required>
                            </div>
                        </div>
                    </div>
                    <div class="row py-2 ">
                        <div class="col col-12">
                            <div class="row align-items-center justify-content-end">
                            <!-- Checkbox -->
                            <div class="col col-auto form-check flex-col">
                                <input class="form-check-input" type="checkbox" id="inlineForce">
                                <label class="form-check-label" for="inlineForce">
                                Force execution
                                </label>
                            </div>
                            <div class="col col-auto flex-col">
                                <button id="btnSubmit" type="submit" class="btn btn-primary btn-md mt-0">Execute</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <hr class="w-100"/>
        
        <div class="row px-3">
            <div class="col-12 pb-3">
                <h5 class="pt-2 pb-0">Result</h5>
                <div class="w-100">
                    <pre id="cmdResult" style="overflow-x:auto;width:100%;">
                    </pre>    
                </div>
            </div>
        </div>
    </section>
    <script>
        $(document).ready(function(){
            $('#btnSubmit').click(function(event){
                $('#btnSubmit').prop('disabled', true);
                $('#btnSubmit').addClass("btn-masked");
                $.ajax({
                    url : 'exec.php',
                    type : 'POST',
                    dataType : 'json',
                    data : {
                        cmd: $('#inlineCommand').val(),
                        force: $('#inlineForce').is(':checked'),
                    },
                    success : (function (json) {
                        if (json.format == "string"){
                            $('#cmdResult').text(json.result);
                        } else {
                            $('#cmdResult').html(jsonText(json.result));
                        }
                        event.preventDefault();
                        $('#btnSubmit').prop('disabled', false);
                        $('#btnSubmit').removeClass("btn-masked");
                    }),
                    error: (function (xhr, ajaxOptions, thrownError) {
                        $('#cmdResult').text('Error '+xhr.status+': '+thrownError);
                        event.preventDefault();
                        $('#btnSubmit').prop('disabled', false);
                        $('#btnSubmit').removeClass("btn-masked");
                    })
                });
                event.preventDefault();
            });
        });
    </script>
<?php 
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"admin/debug",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
<?php endif; ?>