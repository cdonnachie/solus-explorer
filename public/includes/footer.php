<?php 
  $priority_json = json_encode($ads_data->priority ?? [], JSON_UNESCAPED_SLASHES);

  function render_ad_slot($slotKey, $w, $h, $ads_data){
    // Expect these keys to exist in your $ads_data:
    // - Sevio:   $ads_data->sevio_inventory_id, ->sevio_account_id, ->units->{$slotKey}['sevio_zone']
    // - Bitmedia:$ads_data->units->{$slotKey}['bm_unit']
    // - AdSense: $ads_data->client_id, ->units->{$slotKey}['as_slot']
    $units = $ads_data->units->$slotKey ?? (object)[];
    $style = "width:{$w}px;height:{$h}px";
    ?>
    <div class="abg ad-slot text-center <?php echo $slotKey; ?> <?php echo ($units->bitmedia_id) ?? ''; ?>"
        id="<?php echo $slotKey; ?>"
        data-slot-key="<?php echo $slotKey; ?>"
        style="<?php echo $style; ?>"

        <?php if (!empty($units->sevio_id)) : ?>
          data-zone="<?php echo htmlspecialchars($units->sevio_id); ?>"
          data-sevio-inventory="<?php echo htmlspecialchars($ads_data->sevio_inventory_id); ?>"
          data-sevio-account="<?php echo htmlspecialchars($ads_data->sevio_account_id); ?>"
        <?php endif; ?>

        <?php if (!empty($units->bitmedia_id)) : ?>
          data-bm-unit="<?php echo htmlspecialchars($units->bitmedia_id); ?>"
        <?php endif; ?>

        <?php if (!empty($units->adsense_id)) : ?>
          data-as-client="<?php echo htmlspecialchars($ads_data->adsense_client_id); ?>"
          data-as-slot="<?php echo htmlspecialchars($units->adsense_id); ?>"
        <?php endif; ?>
    ></div>
    <?php
  }
?>

</div>

<!-- Search Not Found Modal -->
<div class="modal fade" id="notFoundModal" tabindex="-1"  style="display: none;" role="dialog" aria-labelledby="modalLabel"
  aria-hidden="true">
  <!-- Change class .modal-sm to change the size of the modal -->
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title w-100 primary-text" id="modalLabel">Object Not Found</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3">
        <p>Searching for:<br> <span id="qValue" class="font-weight-bold"></span><br><br>
        <span class="text-danger font-weight-bold">No objects were found!</span><br><br>
        Please, check and correct you request or try again later.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Search Not Found Modal  -->  

<!-- Copyright -->
<?php if (!isset($settings["copyright_enabled"]) || $settings["copyright_enabled"]) : ?>
  <footer>
  <div class="ct primary-text text-center">
    <div class="col-12 font-small">
      <?php if (!isset($settings["copyright_message"]) || empty($settings["copyright_message"])) {
        echo 'Powered by <a class="primary-text" href="'.$project_url.'" target="_blank" rel="noopener">Solus Explorer</a>';
      } else {
        echo strtr($settings["copyright_message"],['!#CY#!' => getCopyrightYears()->html]);
      }
      ?>
    </div>
  </div>
  </footer>
<?php endif; ?>
<!--/.Copyright-->


<?php 
// <!-- Ads -->
if ($ads_data->enabled && !$is_admin_area) : ?>
<footer class="page-footer-mix" info="p-0 mt-5 rgba-stylish-light">
  <div class="row p-0 m-0 justify-content-end">
    <div class="col w-100 p-0 m-0">
      <div class="row mix-row p-0 m-0 justify-content-around">
        <div class="col-auto mix-l p-0 m-0">
          <!-- left slot -->
          <?php render_ad_slot('ftr-l-320', 320, 100, $ads_data); ?>
          <?php render_ad_slot('ftr-l-728', 728, 90,  $ads_data); ?>
        </div>
        <div class="col-auto mix-m p-0 m-0">
          <!-- middle slot -->
          <?php render_ad_slot('ftr-m-320', 320, 100, $ads_data); ?>
        </div>
        <div class="col-auto mix-r p-0 m-0">
          <!-- right slot -->
          <?php render_ad_slot('ftr-r-320', 320, 100, $ads_data); ?>
          <?php render_ad_slot('ftr-r-728', 728, 90,  $ads_data); ?>
        </div>
      </div>
    </div>
    <?php if(!empty($cmp_on)): // set in header.php: CMP shown to this visitor ?>
    <div class="col-auto cmp-spacer p-0 m-0"></div>
    <?php endif; ?>
  </div>  
</footer>
<script nonce="<?php echo $csp_nonce; ?>">
  window.AD_PRIORITY = <?php echo $priority_json; ?>;
</script>
<?php endif; 
//<!-- /.Ads -->

// <!-- SCRIPTS --> ?>
  <script defer nonce="<?php echo $csp_nonce; ?>" type="text/javascript" >getMetrics('<?php echo $settings["explorer_address"];?>','/api/monitoring/uxm/',rid)</script>
  <?php 
  // ADS WATERFALL 
    if ($ads_data->enabled && !$is_admin_area) :
  ?>
  <script defer nonce="<?php echo $csp_nonce; ?>">
  (function(){
    const PRIORITY = Array.isArray(window.AD_PRIORITY) ? window.AD_PRIORITY : ['sevio','bitmedia'];
    // Per-provider render timeout before falling through to the next provider.
    // settings "ads_fallback_ms": a number (all providers) or a map such as
    // ["sevio" => 3500, "bitmedia" => 900, "adsense" => 900, "default" => 900].
    // Sevio loads a ~450 KB Prebid wrapper and runs an auction, so it gets 3500 ms
    // unless configured explicitly.
    const FALLBACK = <?php
      $fb = $settings["ads_fallback_ms"] ?? 1000;
      $fb = is_array($fb) ? $fb : ["default" => (int)$fb];
      $fb += ["default" => 1000, "sevio" => 3500];
      echo json_encode(array_map('intval', $fb));
    ?>;
    const fallbackMs = (p) => FALLBACK[p] ?? FALLBACK['default'];

    function isVisible(el){
      if (!el) return false;
      const cs = getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0') return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0;
    }

    // One observer per slot for the lifetime of the page: boot() re-runs on every
    // resize/orientation change and must not allocate a fresh observer each time.
    const observed = new WeakSet();
    function waitVisibleThen(el, fn){
      if (!('IntersectionObserver' in window)) { if (isVisible(el)) fn(); return; }
      if (isVisible(el)) { fn(); return; }
      if (observed.has(el)) return;
      observed.add(el);
      const io = new IntersectionObserver(entries=>{
        entries.forEach(e=>{
          if (e.isIntersecting) { io.unobserve(el); fn(); }
        });
      }, {threshold: 0.1});
      io.observe(el);
    }

    // Load Sevio bundle once
    let sevLoaded = false, sevLoading = false, sevLoadCbs = [];
    function loadSevioBundle(cb){
      if (sevLoaded) { cb(); return; }
      sevLoadCbs.push(cb);
      if (sevLoading) return;
      sevLoading = true;
      const s = document.createElement('script');
      s.async = true;
      s.crossOrigin = 'anonymous';
      s.src = 'https://cdn.adx.ws/scripts/loader.js';
      s.onload = () => { sevLoaded = true; sevLoading = false; sevLoadCbs.splice(0).forEach(f=>f()); };
      s.onerror = () => { sevLoading = false; sevLoadCbs.splice(0).forEach(f=>f()); };
      document.head.appendChild(s);
    }

    // Load AdSense bundle once (needs client id; any one slot can supply it)
    let asLoaded = false, asLoading = false, asLoadCbs = [];
    function loadAdSenseBundle(clientId, cb){
      if (asLoaded) { cb(); return; }
      asLoadCbs.push(cb);
      if (asLoading) return;
      asLoading = true;
      const s = document.createElement('script');
      s.async = true;
      s.crossOrigin = 'anonymous';
      s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='+encodeURIComponent(clientId);
      s.onload = () => { asLoaded = true; asLoading = false; asLoadCbs.splice(0).forEach(f=>f()); };
      s.onerror = () => { asLoading = false; asLoadCbs.splice(0).forEach(f=>f()); };
      document.head.appendChild(s);
    }

    function trySevio(slotEl, onDone){
      const zone = slotEl.getAttribute('data-zone');
      const inventoryId = slotEl.getAttribute('data-sevio-inventory');
      const accountId   = slotEl.getAttribute('data-sevio-account');
      <?php if($settings["debug_enabled"]): ?>
      console.log(`* [${slotEl.id}] trying Sevio (zone ${zone})`);
      <?php endif; ?>
      if (!zone || !inventoryId || !accountId) { 
        <?php if($settings["debug_enabled"]): ?>
        console.log(`- [${slotEl.id}] Sevio no zone defined`);
        <?php endif; ?>
        onDone(false); return; }

      loadSevioBundle(()=>{
        const t0 = performance.now();
        const prefs = { adType: 'banner', zone, inventoryId, accountId };
        slotEl.classList.add('sevioads');
        window.sevioads = window.sevioads || [];
        window.sevioads.push([prefs]);

        // Detect success: data-rendered="true" OR iframe added
        //const cancel = armDetection(slotEl, onDone);
        const cancel = armDetection(slotEl, ok=>{
          if (ok) log('sevio',slotEl,(performance.now()-t0).toFixed(0));onDone(ok);
        });
        // Give it up to FALLBACK_MS to render
        //setTimeout(()=>{ cancel() || onDone(false); }, fallbackMs('sevio'));
        setTimeout(()=>{ if(!cancel()) {
        <?php if($settings["debug_enabled"]): ?>
          console.log(`- [${slotEl.id}] Sevio timeout after ${fallbackMs('sevio')} ms`);
        <?php endif; ?>
        onDone(false);}}, fallbackMs('sevio'));
      });
    }

    function tryBitmedia(slotEl, onDone){
      const t0 = performance.now();
      const unit = slotEl.getAttribute('data-bm-unit');
      <?php if($settings["debug_enabled"]): ?>
      console.log(`* [${slotEl.id}] trying Bitmedia (unit ${unit})`);
      <?php endif ?>
      if (!unit) {  
        <?php if($settings["debug_enabled"]): ?>
        console.log(`- [${slotEl.id}] Bitmedia no unit defined`);
        <?php endif ?>
        onDone(false); return; }

      // Replace contents with <ins data-unit="{unit}">
      const ins = document.createElement('ins');
      ins.className = 'abg';
      ins.style.cssText = slotEl.getAttribute('style') || '';
      ins.setAttribute('data-unit', unit);
      slotEl.innerHTML = '';
      slotEl.appendChild(ins);

      // Inject Bitmedia unit loader (per-unit)
      (function(unitCode){
        !function(e,n,c,t,o,r,d){
          !function e(n,c,t,o,r,m,d,s,a){
            s=c.getElementsByTagName(t)[0],
            (a=c.createElement(t)).async=!0,
            a.src="https://"+r[m]+"/js/"+o+".js?v="+d,
            a.onerror=function(){ a.remove(); (m+=1)>=r.length || e(n,c,t,o,r,m) },
            s.parentNode.insertBefore(a,s)
          }(window,document,"script",unitCode,["cdn.bmcdn6.com"],0,new Date().getTime())
        }();
      })(unit);

      // const cancel = armDetection(slotEl, onDone);
      const cancel = armDetection(slotEl, ok=>{
        if (ok) log('bitmedia',slotEl,(performance.now()-t0).toFixed(0));
        onDone(ok);
      });
      //setTimeout(()=>{ cancel() || onDone(false); }, fallbackMs('bitmedia'));
      setTimeout(()=>{ if(!cancel()) {
        <?php if($settings["debug_enabled"]): ?>
        console.log(`- [${slotEl.id}] Bitmedia timeout after ${fallbackMs('bitmedia')} ms`);
        <?php endif ?>
        onDone(false);} }, fallbackMs('bitmedia'));
    }

    function tryAdSense(slotEl, onDone){
      const client = slotEl.getAttribute('data-as-client');
      const slot   = slotEl.getAttribute('data-as-slot');
      const t0 = performance.now();
      <?php if($settings["debug_enabled"]): ?>
      console.log(`* [${slotEl.id}] trying adsense (slot-id ${slot})`);
      <?php endif ?>
      if (!client || !slot) { 
        <?php if($settings["debug_enabled"]): ?>
        console.log(`- [${slotEl.id}] adsense no slot defined`); 
        <?php endif ?>
      onDone(false); return; }

      loadAdSenseBundle(client, ()=>{
        // Create <ins class="adsbygoogle"> with same dimensions
        const style = slotEl.getAttribute('style') || '';
        const ins = document.createElement('ins');
        ins.className = 'adsbygoogle';
        ins.style.cssText = style;
        ins.setAttribute('data-ad-client', client);
        ins.setAttribute('data-ad-slot',   slot);
        slotEl.innerHTML = '';
        slotEl.appendChild(ins);

        try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch(_){}

        // const cancel = armDetection(slotEl, onDone);
        const cancel = armDetection(slotEl, ok=>{
          if (ok) log('adsense',slotEl,(performance.now()-t0).toFixed(0));
          onDone(ok);
        });
        //setTimeout(()=>{ cancel() || onDone(false); }, fallbackMs('adsense'));
        setTimeout(()=>{ if(!cancel()) {
          <?php if($settings["debug_enabled"]): ?>
          console.log(`- [${slotEl.id}] adsense timeout after ${fallbackMs('adsense')} ms`);
          <?php endif ?>
          onDone(false);} }, fallbackMs('adsense'));
      });
    }

    // Returns a cancel() function. If it returns true, detection already observed success.
    function armDetection(slotEl, onDone){
      // If provider sets data-rendered="true" on the slot or its child – watch both:
      let finished = false;

      const seenSuccess = () => {
        if (finished) return true;
        // success if: slot has an iframe OR any child iframe OR data-rendered="true"
        if (slotEl.getAttribute('data-rendered') === 'true') return true;
        if (slotEl.querySelector('iframe')) return true;
        return false;
      };

      // Observe attributes & childList
      const moAttrs = new MutationObserver(muts=>{
        if (seenSuccess()) { finished = true; cleanup(); onDone(true); }
      });
      moAttrs.observe(slotEl, { attributes:true, attributeFilter:['data-rendered'] });

      const moKids = new MutationObserver(()=>{
        if (seenSuccess()) { finished = true; cleanup(); onDone(true); }
      });
      moKids.observe(slotEl, { childList:true, subtree:true });

      function cleanup(){
        try{ moAttrs.disconnect(); }catch(_){}
        try{ moKids.disconnect(); }catch(_){}
      }

      // cancel() returns true if already finished (prevents double-callback)
      return function cancel(){
        if (finished) return true;
        if (seenSuccess()) { finished = true; cleanup(); onDone(true); return true; }
        cleanup();
        return false;
      };
    }

    const providerFns = {
      sevio:    trySevio,
      bitmedia: tryBitmedia,
      adsense:  tryAdSense
    };

    function serveWithWaterfall(slotEl){
      if (slotEl.dataset.waterfallDone) return;
      slotEl.dataset.waterfallDone = '1';

      const order = PRIORITY.filter(p => providerFns[p]); // valid only
      if (!order.length) return;

      let i = 0;
      const next = (ok) => {
        if (ok === true) return; // success
        if (i >= order.length) {
          // Optional: put house ad here
          // slotEl.innerHTML = '<a href="/pivx/" class="house-ad">Explore PIVX</a>';
          return;
        }
        const p = order[i++];
        providerFns[p](slotEl, next);
      };
      next(false);
    }

    function boot(){
      document.querySelectorAll('.ad-slot').forEach(slot=>{
        waitVisibleThen(slot, () => serveWithWaterfall(slot));
      });
    }

    function log(p,s,t){
      key=s.getAttribute('data-slot-key');
      const payload = {
        ts: Date.now(),
        pg: location.origin + location.pathname,
        adv:p,sl:key,lt:t,
        r:'<?php echo db_createRequest(); ?>'
      }
      <?php if($settings["debug_enabled"]): ?>
      console.log(`+ [${s}] ${p} fill in ${t} ms`);
      <?php endif ?>
      fetch('<?php echo $settings["explorer_address"];?>/api/monitoring/adsmetrics/', {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          // IMPORTANT: no cookies/credentials at all
          credentials: "omit",
          body: JSON.stringify(payload),
          cache: "no-store"
      }).catch(() => {});
    }

    let bootTimer = null;
    function bootDebounced(){
      clearTimeout(bootTimer);
      bootTimer = setTimeout(boot, 200);
    }

    document.addEventListener('DOMContentLoaded', boot);
    window.addEventListener('resize', bootDebounced);
    window.addEventListener('orientationchange', bootDebounced);
  })();
  </script>
  <?php 
  endif; 
  // EOF ADS WATERFALL ?>
 <?php // <!-- #3rd party scripts --> ?>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/popper.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/bootstrap.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/addons/jquery.dataTables.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/addons/dataTables.bootstrap4.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.4.0/perfect-scrollbar.min.js" integrity="sha384-kUOB3zJp3LsOIl65Vg10pHRNJUtopY0kS34T7UkILEgYVzN7fT77uxXWpH2Wa8jJ" crossorigin="anonymous"></script>
  <?php if (!empty($use_amcharts)) : ?>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdn.amcharts.com/lib/version/4.10.40/core.js" integrity="sha384-ordGF512qZ28dR6Fu0XRiEDmJ2hlTRqB4CUbHADX01rzXhP7hfuURgnA83jHsPAu" crossorigin="anonymous"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdn.amcharts.com/lib/version/4.10.40/charts.js" integrity="sha384-IrVbZoOciaDw6EuKzbIx3dkSw3QyUm/YyYS2Q6GUGXOyZkDoaqZNOuwDpopQymF8" crossorigin="anonymous"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdn.amcharts.com/lib/version/4.10.40/themes/animated.js" integrity="sha384-50bHMQnvdDZTCzEAXttOJXnmLXdJbzKx5ERCSfx+a4I6zyCcNNrnEaTbdNqyGaAI" crossorigin="anonymous"></script>
  <?php if (!empty($settings["amcharts_license_key"])) : ?>
  <script nonce="<?php echo $csp_nonce; ?>">am4core.addLicense(<?php echo json_encode((string)$settings["amcharts_license_key"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);</script>
  <?php endif; ?>
  <?php endif; ?>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/Chart.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/jquery.easypiechart.min.js"></script>
  <script defer nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/addons/download.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/sidenav.js?v=<?php echo $version; ?>"></script>
  <?php if ($beacon_on) :?>
  <script defer nonce="<?php echo $csp_nonce; ?>" src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='<?php echo htmlspecialchars(json_encode(["token" => $settings["cloudflare_beacon_token"]]), ENT_QUOTES); ?>'></script>
  <?php endif; 
   // <!--Custom scripts-->
  ?>
  <script nonce="<?php echo $csp_nonce;?>">
    $(document).ready(function() {
      setMenuItemActive("<?php echo $page_id; ?>");
      const sideNavScrollbar = document.querySelector('.custom-scrollbar');
      const ps = new PerfectScrollbar(sideNavScrollbar);
    });
  </script>
</body>

</html>
