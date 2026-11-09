<?php 
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
  $ha = getHostAddress();
?>

<!--Section: -->
<section>
<!--Grid row-->
<div class="row">
  <div class="col-12 mb-4">
    <div class="card col-12 p-4">
      <h2 class="pb-4 "><?php if (!isset($settings["is_oem"]) || !$settings["is_oem"]) { echo "Solus ";} ?><?php echo ui_getCoinName(); ?> Explorer API </h2>
      <h5 class="pb-3 text-center">
      </h5>
      <h6 class="px-2 pb-1 text-justify">
        The <?php if (!isset($settings["is_oem"]) || !$settings["is_oem"]) { echo "Solus ";} ?> <?php echo ui_getCoinName(); ?> 
        Explorer offers a feature-rich API for easy integration with third-party apps and web services.<br />
        All endpoints are accessible via simple HTTP GET requests.
      </h6>
      <h6 class="px-2 py-3 text-justify text-danger">
      Rate limits:
      </h6>
      <ul>  
        <li>Default: <b><?php echo $settings['api_max_requests_per_burst_period']; ?> requests per IP within <?php echo $settings['api_burst_period_seconds']; ?> seconds</b></li>
        <li>Short bursts are supported</li>
        <li>If you need a higher limit, please contact the <b>Coin development team</b> or the <b>Explorer developers</b> to request an increase.</li>
      </ul>
    </div>
  </div>
</div>
<!--/Grid row-->

<!--Grid row-->
<div class="row">
  <!--Grid column I-->
  <div class="col-12 flex-col mb-4">
    <div class="card col-12 px-0">
    
      <div class="card-header primary-color card-header-text text-left py-2 my-0">
        <span class="h4 h4-responsive"><i class="fas fa-list mr-1" aria-hidden="true"></i> API Calls List</span>
      </div>

      <div class="card-body text-left pt-4">
        <div class="table-container px-2">
          <table class="table table-stripped table-borderless table-sm table-hover col-12">
            <tbody>
              <tr class="text-left">
                <td class="td-sm"  style="width: 15rem;"><a class="text-primary" href="#getblockcount">getblockcount</a></td>
                <td class="td-sm">Get currend blockchain height.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getdifficulty">getdifficulty</a></td>
                <td class="td-sm">Get current blockchain difficulty.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getnetworkhashps">getnetworkhashps</a></td>
                <td class="td-sm">Get current network hash rate.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getconnectioncount">getconnectioncount</a></td>
                <td class="td-sm">Get number of explorer peer connections.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getcoinsupply">getcoinsupply</a></td>
                <td class="td-sm">Get current coin supply.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getmoneysupply">getmoneysupply</a></td>
                <td class="td-sm">Get current coin supply as plain text.</td>
              </tr>
              <?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getmasternodecount">getmasternodecount</a></td>
                <td class="td-sm">Get current number of masternodes in the network.</td>
              </tr>
              <?php endif; ?>
              <?php if (isset($settings['rvn_assets']) && $settings['rvn_assets']) : ?>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getassetcount">getassetcount</a></td>
                <td class="td-sm">Get number of assets per type.</td>
              </tr>
              <?php endif; ?>
              <?php if (isset($settings['rvn_assets']) && $settings['rvn_assets']) : ?>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getassetinfo">getassetinfo</a></td>
                <td class="td-sm">Get information about asset.</td>
              </tr>
              <?php endif; ?>
              <?php if (isset($settings['rvn_assets']) && $settings['rvn_assets']) : ?>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getaddressmessages">getaddressmessages</a></td>
                <td class="td-sm">Get asset messages sent to the specified address.</td>
              </tr>
              <?php endif; ?>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getblockhash">getblockhash</a></td>
                <td class="td-sm">Get hash of block by height.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getblock">getblock</a></td>
                <td class="td-sm">Get block contents by block height or block hash.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getblocktemplate">getblocktemplate</a></td>
                <td class="td-sm">Get block template contents.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getrawtransaction">getrawtransaction</a></td>
                <td class="td-sm">Get raw transaction contents with optional decryption.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getbalance">getbalance</a></td>
                <td class="td-sm">Get address balance.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getaddress">getaddress</a></td>
                <td class="td-sm">Get address information.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getaddressgroup">getaddressgroup</a></td>
                <td class="td-sm">Get address group information.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getmarketcapitalization">getmarketcapitalization</a></td>
                <td class="td-sm">Get latest Coin market capitalization.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getmarketinfo">getmarketinfo</a></td>
                <td class="td-sm">Get latest Coin market information.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getrichlist">getrichlist</a></td>
                <td class="td-sm">Get list of addresses with the most coins.</td>
              </tr>
              <tr class="text-left">
                <td class="td-sm"><a class="text-primary" href="#getaddnodelist">getaddnodelist</a></td>
                <td class="td-sm">Get addnode list.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<!--/Grid row-->

<!--Grid row-->
<div class="row">
  <!--Grid column I-->
  <div class="col-12 flex-col mb-4">
  
    <div class="card col-12 px-0">
      <div class="card-header primary-color card-header-text text-left py-2 my-0">
        <span class="h4 h4-responsive"><i class="fas fa-code-branch mr-1" aria-hidden="true"></i> API Calls Decription</span>
      </div>

      <!--getblockcount content-->
        <a name="getblockcount"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger">
            getblockcount
          </div>

          <div class="col-12 default-category py-2">
            Returns current blockchain height.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return blockchain height as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getblockcount/'?>" target="_blank">
                <?php echo $ha.'/api/getblockcount/'?>
              </a><br>
              <a href="<?php echo $ha.'/api/getblockcount/?legacy=1'?>" target="_blank">
                <?php echo $ha.'/api/getblockcount/?legacy=1'?>
              </a><br>
            </div>
            
            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">blockcount</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Current blockchain height reported by daemon.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Current blockchain height reported by daemon.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getblockcount content-->
      <hr>
      <!--getdifficulty content-->
        <a name="getdifficulty"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getdifficulty
          </div>

          <div class="col-12 default-category py-2">
            Returns current blockchain difficulty.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return blockchain difficulty as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getdifficulty/'?>" target="_blank">
                <?php echo $ha.'/api/getdifficulty/'?>
              </a><br>
              <a href="<?php echo $ha.'/api/getdifficulty/?legacy=1'?>" target="_blank">
                <?php echo $ha.'/api/getdifficulty/?legacy=1'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">difficulty_raw</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current blockchain difficulty reported by daemon.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">difficulty_hr</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current blockchain difficulty in human readable format. 
                    <br>Should be used together with 'dimension'.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">dimension</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Dimension of 'difficulty_hr' according to SI standard: <br>k = kilo, M = mega, G = giga, T = tera, P = peta, E = exa, Z = zetta and Y = yotta.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current blockchain difficulty reported by daemon.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getdifficulty content-->
      <hr>
      <!--getnetworkhashps content-->
        <a name="getnetworkhashps"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getnetworkhashps
          </div>

          <div class="col-12 default-category py-2">
            Returns current blockchain hash rate.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return blockchain hash rate as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getnetworkhashps/'?>" target="_blank">
                <?php echo $ha.'/api/getnetworkhashps/'?>
              </a><br>
              <a href="<?php echo $ha.'/api/getnetworkhashps/?legacy=1'?>" target="_blank">
                <?php echo $ha.'/api/getnetworkhashps/?legacy=1'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">hashrate_raw</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current blockchain hash rate reported by daemon.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">hashrate_hr</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current blockchain hash rate in human readable format. 
                    <br>Should be used together with 'dimension'.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">dimension</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Dimension of 'hashrate_hr' according to SI standard: <br>k = kilo, M = mega, G = giga, T = tera, P = peta, E = exa, Z = zetta and Y = yotta.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current blockchain hash rate reported by daemon.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getnetworkhashps content-->
      <hr>
      <!--getconnectioncount content-->
        <a name="getconnectioncount"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getconnectioncount
          </div>

          <div class="col-12 default-category py-2">
            Returns current number of connected to the explorer daemon peers.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return total number of connections as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getconnectioncount/'?>" target="_blank">
                <?php echo $ha.'/api/getconnectioncount/'?>
              </a><br>
              <a href="<?php echo $ha.'/api/getconnectioncount/?legacy=1'?>" target="_blank">
                <?php echo $ha.'/api/getconnectioncount/?legacy=1'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">total</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Total number of connections reported by daemon.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">inbound</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Number of inbound connections reported by daemon.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">outbound</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Number of outbound connections reported by daemon.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">ipv4</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Number of connections over IP version 4.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">ipv6</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Number of connections over IP version 6.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Total number of connections reported by daemon.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getconnectioncount content-->      
      <hr>
      <!--getcoinsupply content-->
        <a name="getcoinsupply"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getcoinsupply
          </div>

          <div class="col-12 default-category py-2">
            Returns current coun supply.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return coin supply as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getcoinsupply/'?>" target="_blank">
                <?php echo $ha.'/api/getcoinsupply/'?>
              </a><br>
              <a href="<?php echo $ha.'/api/getcoinsupply/?legacy=1'?>" target="_blank">
                <?php echo $ha.'/api/getcoinsupply/?legacy=1'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">coinsupply</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current coin supply reported by explorer database.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current coin supply reported by explorer database.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getcoinsupply content-->
      <!--getmoneysupply content-->
      <a name="getmoneysupply"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getmoneysupply
          </div>

          <div class="col-12 default-category py-2">
            Returns current coun supply as plain text.
          </div>
          <div class="col-12 default-category py-2">
          <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">p</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>Decimal precision.<br> 
                  If set to 0 reply will return an integer value.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getmoneysupply'?>" target="_blank">
                <?php echo $ha.'/api/getmoneysupply'?>
              </a><br>
              <a href="<?php echo $ha.'/api/getmoneysupply/?p=0'?>" target="_blank">
                <?php echo $ha.'/api/getmoneysupply/?p=0'?>
              </a><br>
            </div>
            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current coin supply reported by explorer database returned as a plain text.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getmoneysupply content-->

      <hr>
      
      <?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
      <!--getmasternodecount content-->
        <a name="getmasternodecount"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getmasternodecount
          </div>

          <div class="col-12 default-category py-2">
            Returns current number of masternodes in the blockchain network.
          </div>
          <div class="col-12 default-category py-2">
            <div class='py-3'>Request has no parameters.</div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getmasternodecount/'?>" target="_blank">
                <?php echo $ha.'/api/getmasternodecount/'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">masternodecount</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(object)</td>
                  <td class="td-sm default-category px-2">Masternode count output may warry between networks. It's mainly depends on the Coin daemon functionality and 
                    for example may contain:<br>total number of masternodes, number of masternodes by the status, number of masternodes by the network protocol, number 
                    of masternodes by tier etc.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getconnectioncount content-->      
      <hr>
      <?php endif; ?>
      <?php if (isset($settings['rvn_assets']) && $settings['rvn_assets']) { 
          ?>

        <!--getassetcount content-->
        <a name="getassetcount"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getassetcount
          </div>

          <div class="col-12 default-category py-2">
            Returns information about asset number per type.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              None
            </div>

            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getassetcount/'; ?>" target="_blank">
                <?php echo $ha.'/api/getassetcount/'; ?>
              </a>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">result</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Status of the requestt processung: 'success' or 'error'.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Timestamp of the request execution measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">assets</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(object)</td>
                  <td class="td-sm default-category px-2">Number of asset by type and aggregated numbers <i>total</i> and <i>non-admin</i><br><br>
                  </td>
              </table>
            </div>
          </div>
        </div> 
        <!--./getassetcount content-->      
        <hr>
        <?php 
        } 
      ?>
      <?php if (isset($settings['rvn_assets']) && $settings['rvn_assets']) {
        $am = db_getAssetMovesDataWithFilter(array(), $limit = 1, $skip = 0); 
        if (count($am)>0) {
          $ast = db_getAssetByName($am[0]->name);
          ?>

        <!--getassetinfo content-->
        <a name="getassetinfo"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getassetinfo
          </div>

          <div class="col-12 default-category py-2">
            Returns information about asset.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">key</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Asset unique key.<br>
                    Unique key is an asset identifier specific to the Solus Explorer realization.<br>
                    <i>Note: one of mandatory parameters ('key' or 'name') must be provided. In case both are provided, <b>key</b> will be used for request processing.</i>
                  </td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">name</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Asset name.<br>
                    <b>Warning!</b> all special characters, e.g. octothorpe or ampersand, must be properly http-encoded.<br>
                    <i>Note: one of mandatory parameters ('key' or 'name') must be provided. In case both are provided, <b>key</b> will be used for request processing.</i>
                  </td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">withtopholders</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return top 100 addresses holding the asset ranked by asset amount.</td>
                </tr>
              </table>
            </div>

            <?php 
            if (count($ast)>0) { 
              $ast = $ast[0];
            ?>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getassetinfo/?key='.uriEncode($ast->key); ?>" target="_blank">
                <?php echo $ha.'/api/getassetinfo/?key='.uriEncode($ast->key); ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getassetinfo/?name='.uriEncode($ast->_id); ?>" target="_blank">
                <?php echo  $ha.'/api/getassetinfo/?name='.uriEncode($ast->_id); ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getassetinfo/?name='.uriEncode($ast->_id).'&withtopholders=1'; ?>" target="_blank">
                <?php echo $ha.'/api/getassetinfo/?name='.uriEncode($ast->_id).'&withtopholders=1'; ?>
              </a><br>
            </div>
            <?php } ?>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">result</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Status of the requestt processung: 'success' or 'error'.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Timestamp of the request execution measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">data</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(object)</td>
                  <td class="td-sm default-category px-2">Data available for the requested asset.<br><br>
                    <b>genesis_timestamp</b> (integer) timestamp of the asset creation transaction;<br>
                    <b>genesis_tx</b> (string) asset genesis transaction hash;<br>
                    <b>ipfs</b> (string) embedded IPFS hash or 'null';<br>
                    <b>key</b> (string) unuque asset key, specific to Solus Explorer;<br>
                    <b>reissuable</b> (integer) reissuable flag: 0 - not reissuable, 1 - reissuable;<br>
                    <b>supply</b> (float) total supply of the asset;<br>
                    <b>type</b> (string) type of the asset;<br>
                    <b>units</b> (integer) decimal precision of the asset amounts;<br>
                    <b>name</b> (string) canonical name of the asset;<br>
                    <b>holders_count</b> (integer) total number of addresses holding the asset;<br>
                    <b>top100_holders</b> (array) [Optional] Requires <i>withtopholders=1</i> list of addresses with top 100 asset balances ;<br>
                  </td>
              </table>
            </div>
          </div>
        </div> 
        <!--./getassetinfo content-->      
        <hr>
        <?php 
          }
        } 
      ?>

      <?php if (isset($settings['rvn_assets']) && $settings['rvn_assets']) {
        $am = db_getAssetMovesDataWithFilter(['message' => ['$exists' => true]], $limit = 1, $skip = 0); 
        ?>

        <!--getaddressmessages content-->
        <a name="getaddressmessages"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getaddressmessages
          </div>

          <div class="col-12 default-category py-2">
            Returns list of asset messages sent to the specified address.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Blockchain address hash.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">since</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>
                    Minimum timestamp of the message in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT). <br>
                    If not set, the request will return messages for the last 24 hours</td>
                </tr>
              </table>
            </div>

            <?php 
              if (count($am)>0) {
                $am = $am[0];
            ?>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getaddressmessages/?address='.$am->address.'&since='.$am->move_time_sec; ?>" target="_blank">
                <?php echo $ha.'/api/getaddressmessages/?address='.$am->address.'&since='.$am->move_time_sec; ?>
              </a>
            </div>
            <?php } ?>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">result</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Status of the requestt processung: 'success' or 'error'.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Timestamp of the request execution measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">data</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(array)</td>
                  <td class="td-sm default-category px-2">Array of messages with following fields:<br><br>
                    <b>timestamp</b> (integer) timestamp of the transaction;<br>
                    <b>tx</b> (string) transaction hash;<br>
                    <b>asset</b> (string) linked asset name;<br>
                    <b>message</b> (string) message value;<br>
                  </td>
              </table>
            </div>
          </div>
        </div> 
        <!--./getaddressmessages content-->      
        <hr>
        <?php 
          }
      ?>

      <!--getblockhash content-->
        <a name="getblockhash"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getblockhash
          </div>

          <div class="col-12 default-category py-2">
            Returns block hash by the block height index.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">index</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer) </td>
                  <td class="td-sm default-category px-2">Block height index.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return block hash as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <?php $lb = db_getLastDbBlockNumber();?>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getblockhash/?index='.$lb; ?>" target="_blank">
                <?php echo $ha.'/api/getblockhash/?index='.$lb; ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getblockhash/?index='.$lb.'&legacy=1'; ?>" target="_blank">
                <?php echo $ha.'/api/getblockhash/?index='.$lb.'&legacy=1'; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">index</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Block height.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">hash</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Block hash reported by daemon.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">block_time</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Block timestamp.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Block hash reported by daemon.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getblockhash content-->
      <hr>
      <!--getblock content-->
        <a name="getblock"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getblock
          </div>

          <div class="col-12 default-category py-2">
            Returns block contents by block index or block hash.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">hash</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Block hash.<br>
                    <i>Note: one of mandatory parameters ('hash' or 'index') must be provided.</i>
                  </td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">index</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer) </td>
                  <td class="td-sm default-category px-2">Block height index.<br>
                    <i>Note: one of mandatory parameters ('hash' or 'index') must be provided.</i></td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <?php $bh = getBlockHash($lb); ?>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getblock/?hash='.$bh; ?>" target="_blank">
                <?php echo $ha.'/api/getblock/?hash='.$bh; ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getblock/?index='.$lb; ?>" target="_blank">
                <?php echo $ha.'/api/getblock/?index='.$lb; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class='py-3'>Request returns direct output of daemon request. Specific format depends on the Coin solution. Please refer to the Coin daemon specification.</div>
          </div>
        </div> 
      <!--./getblock content-->
      <hr>
      <!--getblocktemplate content-->
        <a name="getblocktemplate"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getblocktemplate
          </div>

          <div class="col-12 default-category py-2">
            Returns block template contents.
          </div>
          <div class="col-12 default-category py-2">
            <div class='py-3'>Request has no parameters.</div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getblocktemplate/'; ?>" target="_blank">
                <?php echo $ha.'/api/getblocktemplate/'; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class='py-3'>Request returns direct output of daemon request. Specific format depends on the Coin solution. Please refer to the Coin daemon specification.</div>
          </div>
        </div>
      <!--./getblocktemplate content-->
      <hr>
      <!--getrawtransaction content-->
        <a name="getrawtransaction"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getrawtransaction
          </div>

          <div class="col-12 default-category py-2">
            Returns transaction contents with optional decoding.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">txid</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Transaction ID hash.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">decode</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return decoded transaction contents.<br> 
                  Otherwise  encoded contents will be returned as a plain text.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <?php 
                $txs = db_getTxsByBlockHeight($lb);
                $txid = $txs[count($txs)-1]->_id;
              ?>
              <a href="<?php echo $ha.'/api/getrawtransaction/?txid='.$txid; ?>" target="_blank">
                <?php echo $ha.'/api/getrawtransaction/?txid='.$txid; ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getrawtransaction/?txid='.$txid.'&decode=1'; ?>" target="_blank">
                <?php echo $ha.'/api/getrawtransaction/?txid='.$txid.'&decode=1'; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class='py-3'>Request returns direct output of daemon request. Specific format depends on the Coin solution. Please refer to the Coin daemon specification.</div>
          </div>
        </div> 
      <!--./getrawtransaction content-->
      <hr>
      <!--getbalance content-->
        <a name="getbalance"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getbalance
          </div>

          <div class="col-12 default-category py-2">
            Returns address balance reported by explorer database.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Blockchain address hash.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">legacy</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return address balance as a plain text.<br> 
                  Otherwise will return JSON formatted data including query execution timestamp.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <?php 
                try {
                  $ar = db_getAddresesWithTopBalance(1)[0];
                  $addr = $ar->_id;
                  $agrp = $ar->group_id;
                } catch (Exception $e){
                  $addr = 'Zzzzz000000000000000000000000000000';
                  $agrp = 'csag-a0000000-b000-c000-d000-e00000000000';
                }
              ?>
              <a href="<?php echo $ha.'/api/getbalance/?address='.$addr; ?>" target="_blank">
                <?php echo $ha.'/api/getbalance/?address='.$addr; ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getbalance/?address='.$addr.'&legacy=1'; ?>" target="_blank">
                <?php echo $ha.'/api/getbalance/?address='.$addr.'&legacy=1'; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Blockchain address hash.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">balance</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current address balance.</td>
                </tr>
              </table>
            </div>

            <div class="pt-2">Returns (legacy=1):</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current address balance reported by explorer database.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getbalance content-->
      <hr>
      <!--getaddress content-->
        <a name="getaddress"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getaddress
          </div>

          <div class="col-12 default-category py-2">
            Returns address information reported by explorer database.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Blockchain address hash.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getaddress/?address='.$addr; ?>" target="_blank">
                <?php echo $ha.'/api/getaddress/?address='.$addr; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Blockchain address hash.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">balance</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Current address balance.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">received</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Total received by address coins.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">sent</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Total sent by address coins.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">groupid</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Identifier of the address group which this address belongs to.</td>
                </tr>
                <?php if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]): ?>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">assets</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(array)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>List of assets address is currently holding.</td>
                </tr>
                <?php endif; ?>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">last_txs</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(array)</td>
                  <td class="td-sm default-category px-2">100 latest transactions of the address.<br><br>
                    <b>tx_time</b> (integer) timestamp of transaction;<br>
                    <b>block_ix</b> (integer) block index of transaction;<br>
                    <b>txid</b> (string) transaction ID hash;<br>
                    <b>amount</b> (float) moved amount;<br>
                    <b>is_reward</b> (boolean) reward flag: true - amount is a part of block reward, false - amount is a transfer between addresses.
                  </td>
                </tr>
              </table>
            </div>

          </div>
        </div> 
      <!--./getaddress content-->
      <hr>
      <!--getaddressgroup content-->
        <a name="getaddressgroup"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getaddressgroup
          </div>

          <div class="col-12 default-category py-2">
            Returns address group information reported by explorer database.
          </div>
          <div class="col-12 default-category py-2">
            <div>Request parameters:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">groupid</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string) </td>
                  <td class="td-sm default-category px-2">Address group ID.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">withaddresslist</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>If set to 1 request will return list of grooup member addresses.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getaddressgroup/?groupid='.$agrp; ?>" target="_blank">
                <?php echo $ha.'/api/getaddressgroup/?groupid='.$agrp; ?>
              </a><br>
              <a href="<?php echo $ha.'/api/getaddressgroup/?groupid='.$agrp.'&withaddresslist=1'; ?>" target="_blank">
                <?php echo $ha.'/api/getaddressgroup/?groupid='.$agrp.'&withaddresslist=1'; ?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">groupid</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Address group ID.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">balance</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Total balance of all addreses in group.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address_count</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Number of addresses in the group.</td>
                </tr>
                <?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">masternode_count</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Number of masternodes activated in a group.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">masternode_list</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(array)</td>
                  <td class="td-sm default-category px-2">Masternode list output may warry between networks. It's mainly depends on the Coin daemon functionality and 
                    for example may contain:<br>rank, network protocol, collateral tx hash, collateral tx output index, status, payee address, activetime etc..</td>
                </tr>
                <?php endif; ?>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">address_list</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(array)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br> Requires <b>withaddresslist=1</b> in the request parameters.<br>
                  List of address group member addresses with its balances.<br><br>
                  <span class="primary-text text-italic">Note: use this option with care, performance may be significantly degraded if group has large number of addresses.</span>
                  </td>
                </tr>
              </table>
            </div>

          </div>
        </div> 
      <!--./getaddress content-->
      <hr>
      <!--getmarketcapitalization content-->
        <a name="getmarketcapitalization"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getmarketcapitalization
          </div>

          <div class="col-12 default-category py-2">
            Returns current coin market capitalization.
          </div>
          <div class="col-12 default-category py-2">
            <div class='py-3'>Request has no parameters.</div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getmarketcapitalization/'?>" target="_blank">
                <?php echo $ha.'/api/getmarketcapitalization/'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Request time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">currencies</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(object)</td>
                  <td class="td-sm default-category px-2">List of currencies for which coin capitalization coalculated.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getmarketcapitalization content-->      
      <hr>
      <!--getmarketinfo content-->
        <a name="getmarketinfo"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getmarketinfo
          </div>

          <div class="col-12 default-category py-2">
            Returns latest available coin market information.
          </div>
          <div class="col-12 default-category py-2">
            <div class='py-3'>Request has no parameters.</div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getmarketinfo/'?>" target="_blank">
                <?php echo $ha.'/api/getmarketinfo/'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">timestamp</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Latest market information update time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">max_price</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Maximum coin price over available markets.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">avg_price</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Volume weighted average price over available markets.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">min_price</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Minimum coin price over available markets.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">volume_base</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">24 hours trading volume in base currency.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">volume_coin</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">24 hours trading volume in Coins.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">percent_change</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">24 hours price change on the highest market in percent.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">avg_percent_change</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Volume weighted 24 hours price change in percent.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">metrics</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(array)</td>
                  <td class="td-sm default-category px-2">Information per each available market.<br><br>
                    <b>exchange</b> (string) exchange identifier;<br>
                    <b>time</b> (datetime) time of market information update;<br>
                    <b>base</b> (string) identifier of base currency;<br>
                    <b>quote</b> (string) identifier of the Coin on the market;<br>
                    <b>latest</b> (float) price of latest trade befoe market information update;<br>
                    <b>percent_change</b> (float) 24 hours price change on the market in percent;<br>
                    <b>base_volume</b> (float) 24 hours trading volume in base currency;<br>
                    <b>quote_volume</b> (float) 24 hours trading volume in Coins;<br>
                    <b>lowest_ask</b> (float) best ask price;<br>
                    <b>highest_bid</b> (float) best bid price;<br>
                  </td>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">base_prices</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(object)</td>
                  <td class="td-sm default-category px-2">List of FIAT prices for base currency.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">base_changes</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(object)</td>
                  <td class="td-sm default-category px-2">List of 24 hours FIAT price changes for base currency in percent.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getmarketinfo content-->      
      <hr>
      <!--getrichlist content-->
        <a name="getrichlist"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getrichlist
          </div>

          <div class="col-12 default-category py-2">
            Returns JSON encoded array of addresses with the most coins.
          </div>
          <div class="col-12 default-category py-2">
          <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left table-secondary ">
                  <th class="th-sm" style="width: 10rem;">Parameter</th>
                  <th class="th-sm text-center" style="width: 10rem;">Value</th>
                  <th class="th-sm">Description</th>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">n</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2"><i>[Optional]</i><br>Number of addresses to return.</td>
                </tr>
              </table>
            </div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getrichlist/?n=50'?>" target="_blank">
                <?php echo $ha.'/api/getrichlist/?n=50'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">rank</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(integer)</td>
                  <td class="td-sm default-category px-2">Rank of the address in the list starting from 1.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">addr</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Blockchain address hash.</td>
                </tr>
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;">bal</td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(float)</td>
                  <td class="td-sm default-category px-2">Balance of the address.</td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      <!--./getrichlist content-->
      <hr>
      <!--getaddnodelist content-->
        <a name="getaddnodelist"></a>
        <div class="card-body text-left pt-4">
          <div class="col-12 h6 text-danger ">
          getaddnodelist
          </div>

          <div class="col-12 default-category py-2">
            Returns addnode list as plain text.
          </div>
          <div class="col-12 default-category py-2">
            <div class='py-3'>Request has no parameters.</div>
            <div>Request example:</div>
            <div class="light-color py-3 px-2">
              <a href="<?php echo $ha.'/api/getaddnodelist/'?>" target="_blank">
                <?php echo $ha.'/api/getaddnodelist/'?>
              </a><br>
            </div>

            <div class="pt-2">Returns:</div>
            <div class="table-container px-2">
              <table class="table table-sm table-hover table-stripped table-borderless col-12">
                <tr class="text-left">
                  <td class="td-sm text-nowrap px-2" style="width: 10rem;"></td>
                  <td class="td-sm text-center px-2" style="width: 10rem;">(string)</td>
                  <td class="td-sm default-category px-2">Addnode list contents as plain text.</td>
                </tr>
              </table>
            </div>
          </div>
        </div> 
      <!--./getaddnodelist content-->      

    </div>
  </div>
  <!--./Grid column I-->

  
</div>
<!--Grid row-->
</section>
<!--/Section: -->
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"api",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>