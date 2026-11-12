<?php
/*
 * Ad waterfall: tries each configured provider in order, per slot.
 *
 * Public pages only. Runs once per document; dataset.waterfallDone is its
 * idempotence guard, so it must never be executed twice.
 */
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
