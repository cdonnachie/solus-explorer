<?php
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';

    global $rpc;
    global $dbm;

    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["overview_precision"];
    $symbol    = $settings["coin_symbol"];

    $enabled = shadow_enabled();

    // live phase / pool state straight from the daemon
    $qq  = $enabled ? rpc_getquantumquasarinfo() : null;
    $grs = $enabled ? rpc_getgoldrushstate() : null;

    // indexed totals from our own records. Served from the cache the loader
    // rebuilds after every session: the underlying aggregations grow with the
    // reward window and are far too slow to run per request.
    $gr         = $enabled ? ui_GetGoldRushData() : null;
    $totals     = is_null($gr) ? null    : $gr->totals;
    $recipients = is_null($gr) ? array() : $gr->recipients;
    $recent     = is_null($gr) ? array() : $gr->recent;

    $tip       = isset($grs->height) ? (int)$grs->height : 0;
    $start_h   = isset($qq->shadow_reward_start_height) ? (int)$qq->shadow_reward_start_height : (int)shadow_cfg("start_height", 0);
    $end_h     = isset($qq->gold_rush_end_height) ? (int)$qq->gold_rush_end_height : (int)shadow_cfg("end_height", 0);
    $mig_h     = isset($qq->quantum_migration_end_height) ? (int)$qq->quantum_migration_end_height : 0;
    $phase     = isset($qq->phase) ? $qq->phase : "unknown";

    $span      = max(1, $end_h - $start_h);
    $elapsed   = min($span, max(0, $tip - $start_h));
    $pct       = round(($elapsed / $span) * 100, 1);
    $remaining = max(0, $end_h - $tip);

    function gr_fmt($v, $p = 2){
        if (is_null($v)) { return "-"; }
        return number_format((float)(string)$v, $p);
    }
?>
<?php if (!$enabled) : ?>
  <div class="row">
    <div class="col-md-12">
      <div class="card card-info p-3">
        <p class="card-text mb-0">Shadow ledger accounting is not enabled for this explorer.</p>
      </div>
    </div>
  </div>
<?php else : ?>

  <!--Section: phase banner-->
  <section>
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header primary-color card-header-text py-2">
            <span class="h4 h4-responsive"><i class="fas fa-bolt mr-1" aria-hidden="true"></i> Gold Rush</span>
            <span class="badge badge-light ml-2"><?php echo htmlspecialchars($phase); ?></span>
          </div>
          <div class="card-body">
            <div class="progress" style="height:1.4rem;">
              <div class="progress-bar primary-color" role="progressbar"
                   style="width: <?php echo $pct; ?>%;"
                   aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100">
                <?php echo $pct; ?>%
              </div>
            </div>
            <div class="row mt-3 text-center">
              <div class="col-6 col-md-3">
                <div class="small text-muted">Reward window</div>
                <div class="h6"><?php echo number_format($start_h); ?> &ndash; <?php echo number_format($end_h); ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div class="small text-muted">Chain height</div>
                <div class="h6"><?php echo number_format($tip); ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div class="small text-muted">Blocks remaining</div>
                <div class="h6"><?php echo number_format($remaining); ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div class="small text-muted">Migration deadline</div>
                <div class="h6"><?php echo $mig_h > 0 ? number_format($mig_h) : "-"; ?></div>
              </div>
            </div>
            <p class="small text-muted mt-3 mb-0">
              Gold Rush pays a scheduled subsidy as deterministic shadow-ledger outputs. They are
              spendable outputs of the active chain but are never written into a block, so they do
              not appear in any block's transaction list.
              <?php if (isset($qq->quantum_spend_enforcement_active) && !$qq->quantum_spend_enforcement_active) : ?>
                Credits remain locked until the Gold Rush window closes.
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--/Section: phase banner-->

  <!--Section: totals-->
  <section>
    <div class="row mt-4">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header primary-color card-header-text py-2">
            <span class="h5 h5-responsive">Credited</span>
          </div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="primary-text">Total credited</td>
                  <td class="text-right"><strong><?php echo gr_fmt($totals->credited, $precision); ?></strong> <?php echo $symbol; ?></td></tr>
              <tr><td class="primary-text">via PoW claims</td>
                  <td class="text-right"><?php echo gr_fmt($totals->pow_credited, $precision); ?> <?php echo $symbol; ?>
                      <span class="text-muted small">(<?php echo number_format($totals->pow_count); ?>)</span></td></tr>
              <tr><td class="primary-text">via PoS credits</td>
                  <td class="text-right"><?php echo gr_fmt($totals->pos_credited, $precision); ?> <?php echo $symbol; ?>
                      <span class="text-muted small">(<?php echo number_format($totals->pos_count); ?>)</span></td></tr>
              <tr><td class="primary-text">Unspent</td>
                  <td class="text-right"><?php echo gr_fmt($totals->unspent, $precision); ?> <?php echo $symbol; ?></td></tr>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header primary-color card-header-text py-2">
            <span class="h5 h5-responsive">Reward pool</span>
          </div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="primary-text">PoW pool</td>
                  <td class="text-right"><?php echo isset($grs->pow_amount) ? gr_fmt(@cdec($grs->pow_amount)/@cdec(100000000), $precision) : "-"; ?> <?php echo $symbol; ?></td></tr>
              <tr><td class="primary-text">PoS pool</td>
                  <td class="text-right"><?php echo isset($grs->pos_amount) ? gr_fmt(@cdec($grs->pos_amount)/@cdec(100000000), $precision) : "-"; ?> <?php echo $symbol; ?></td></tr>
              <tr><td class="primary-text">PoW claims seen</td>
                  <td class="text-right"><?php echo isset($grs->pow_count) ? number_format($grs->pow_count) : "-"; ?></td></tr>
              <tr><td class="primary-text">PoS credits seen</td>
                  <td class="text-right"><?php echo isset($grs->pos_count) ? number_format($grs->pos_count) : "-"; ?></td></tr>
            </table>
            <p class="small text-muted mt-2 mb-0">Pool figures are reported live by the daemon.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--/Section: totals-->

  <!--Section: recipients + recent-->
  <section>
    <div class="row mt-4">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header primary-color card-header-text py-2">
            <span class="h5 h5-responsive">Top quantum recipients</span>
          </div>
          <div class="card-body table-responsive text-nowrap">
            <table class="table table-hover table-striped table-bordered table-sm" width="100%">
              <thead><tr class="table-secondary text-center">
                <th class="th-sm">Address</th><th class="th-sm">Payouts</th><th class="th-sm">Credited</th>
              </tr></thead>
              <tbody>
              <?php foreach ($recipients as $r) : ?>
                <tr>
                  <td><a href="<?php echo $settings["explorer_address"]; ?>/address/?address=<?php echo $r->_id; ?>"><?php echo $r->_id; ?></a></td>
                  <td class="text-center"><?php echo number_format($r->count); ?></td>
                  <td class="text-right"><?php echo gr_fmt(@cdec($r->amount), $precision); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($recipients) == 0) : ?>
                <tr><td colspan="3" class="text-center text-muted">No payouts indexed yet.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header primary-color card-header-text py-2">
            <span class="h5 h5-responsive">Recent payouts</span>
          </div>
          <div class="card-body table-responsive text-nowrap">
            <table class="table table-hover table-striped table-bordered table-sm" width="100%">
              <thead><tr class="table-secondary text-center">
                <th class="th-sm">Block</th><th class="th-sm">Ch.</th><th class="th-sm">Recipient</th><th class="th-sm">Amount</th>
              </tr></thead>
              <tbody>
              <?php foreach ($recent as $p) :
                      $mode = isset($p->shadow_mode) ? $p->shadow_mode : "-"; ?>
                <tr>
                  <td class="text-center"><a href="<?php echo $settings["explorer_address"]; ?>/block/?blockheight=<?php echo $p->block_ix; ?>"><?php echo number_format($p->block_ix); ?></a></td>
                  <td class="text-center"><span class="badge <?php echo ($mode=="pow")?"badge-warning":"badge-info"; ?>"><?php echo strtoupper($mode); ?></span></td>
                  <td><a href="<?php echo $settings["explorer_address"]; ?>/address/?address=<?php echo $p->address; ?>"><?php echo substr($p->address,0,22); ?>&hellip;</a></td>
                  <td class="text-right"><?php echo gr_fmt(@cdec($p->amount), $precision); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($recent) == 0) : ?>
                <tr><td colspan="4" class="text-center text-muted">No payouts indexed yet.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--/Section: recipients + recent-->

<?php endif; ?>
