function normalizeToScale(value, scale, precision){
    if (scale == 'Y'){
        return (value/1000000000000000000000000).toFixed(precision);
    } else if (scale == 'Z'){
        return (value/1000000000000000000000).toFixed(precision);
    } else if (scale == 'E'){
        return (value/1000000000000000000).toFixed(precision);
    } else if (scale == 'P'){
        return (value/1000000000000000).toFixed(precision);
    } else if (scale == 'T'){
        return (value/1000000000000).toFixed(precision);
    } else if (scale == 'G'){
        return (value/1000000000).toFixed(precision);
    } else if (scale == 'M'){
        return (value/1000000).toFixed(precision);
    } else if (scale == 'k'){
        return (value/1000).toFixed(precision);
    } else {
        return value.toFixed(precision);
    }
}

function normalizeSi(value, precision){
    if (value >= 1000000000000000000000000) {
        return (value/1000000000000000000000000).toFixed(precision)+'Y';
    } else if (value >= 1000000000000000000000) {
        return (value/1000000000000000000000).toFixed(precision)+'Z';
    } else if (value >= 1000000000000000000) {
        return (value/1000000000000000000).toFixed(precision)+'E';
    } else if (value >= 1000000000000000){
        return (value/1000000000000000).toFixed(precision)+'P';
    } else if (value >= 1000000000000) {
        return (value/1000000000000).toFixed(precision)+'T';
    } else if (value >= 1000000000) {
        return (value/1000000000).toFixed(precision)+'G';
    } else if (value >= 1000000) {
        return (value/1000000).toFixed(precision)+'M';
    } else if (value >= 1000) {
        return (value/1000).toFixed(precision)+'k';
    } else {
        return value.toFixed(precision);
    }
}

function normalizeMoney(value, precision){

    if (value >= 1000000000) {
        return (value/1000000000).toFixed(precision)+'B';
    } else if (value >= 1000000) {
        return (value/1000000).toFixed(precision)+'M';
    } else if (value >= 1000) {
        return (value/1000).toFixed(precision)+'k';
    } else {
        return value.toFixed(precision);
    }
}

function getHash(str, algo = "SHA-512") {
    let data = new TextEncoder('utf-8').encode(str);
    const hash = crypto.subtle.digest(algo, data);
    const res = Array.from(new Uint8Array(hash)).map(b => b.toString(16).padStart(2, '0')).join('');
    return res;
}

function sha512(str) {
    return crypto.subtle.digest("SHA-512", new TextEncoder("utf-8").encode(str)).then(buf => {
      return Array.prototype.map.call(new Uint8Array(buf), x=>(('00'+x.toString(16)).slice(-2))).join('');
    });
  }

function jsonText(json) {
	json = JSON.stringify(json, undefined, 2);
	json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
		var cl = 'json_number';
		if (/^"/.test(match)) {
			if (/:$/.test(match)) {	
				cl = 'json_key';
			} else {
				cl = 'json_string';
			}
		} else if (/true|false/.test(match)) {
			cl = 'json_bool';
		} else if (/null/.test(match)) {
			cl = 'json_null';
		}
		return '<span class="' + cl + '">' + match + '</span>';
	});
}

function loadPage(pageName, pageTitle, menuItem, contentSource){
        // Set Page Properties
        document.title = pageTitle;
        $("#pageName").html(pageName);
        $('#'+menuItem).addClass("active");
        // Load content
        if (contentSource != null){
            $("#content").load(contentSource);
        }
}

function setMenuItemActive(id){
  el = document.getElementById(id);
  if (el){
    el.classList.add("active");
  }
}

function doSearch(rootLink, q){
    $.ajax({
      url : rootLink+'/_dp/searchdp.php',
      type : 'POST',
      dataType : 'json',
      data : {
        q: q
      },
      success : (function ( json ) {
        var rnum = json.resultsNumber;
        var rtype  = json.objectType;
        var val  = json.objectValue;
        switch(rtype) {
          case 'address':
            // address found
            window.location = rootLink+'/address/?address='+val;
            break;
          case 'block_ix':
            // block height found
            window.location = rootLink+'/block/?blockheight='+val;
            break;
          case 'blockhash':
            // block hash found
            window.location = rootLink+'/block/?blockhash='+val;
            break;
          case 'tx':
            // tx found
            window.location = rootLink+'/tx/?txid='+val;
            break;
          case 'addressgroup':
            // address group found
            window.location = rootLink+'/addressgroup/?groupid='+val;
            break;
          case 'asset':
            // asset object found
            window.location = rootLink+'/asset/?name='+val;
            break;
          case 'asset_id':
            // asset object found
            window.location = rootLink+'/asset/?asset_id='+val;
            break;
          case 'many':
            // multiple objects found
            break;    
          default:
            // none -> nothing found
            $('#qValue').text(q);
            $('#notFoundModal').modal('show');
            
        }
      }),
    });
    event.preventDefault();
}

// Export datatable to CSV
function exportToCsv(url, filename, btn){
    $.ajax({
        url: url, 
        success: download.bind(true, "text/plain", filename)
    });
}

//   Page Update Scripts   //  
function updateOverview(host=''){
    $.ajax({
        url: host+'/_up/overviewup.php',
        success: function(data) {
            if (data.ess.status) {$('#bcText').removeClass('grey-text');} else {$('#bcText').addClass('grey-text');}
            $('#bcText').html(data.bc.text);

            if (data.ess.status) {$('#asText').removeClass("grey-text").addClass("dark-grey-text");} else {$('#asText').removeClass("dark-grey-text").addClass("grey-text");}
            $('#asText').html(data.abs.text);

            if (data.ess.status) {$('#csText').removeClass('grey-text');} else {$('#csText').addClass('grey-text');}
            $('#csText').html(data.cs.whole+".<small>"+data.cs.fraction+"</small>");

            if (data.ess.status) {$('#zcText').removeClass("grey-text").addClass("dark-grey-text");} else {$('#zcText').removeClass("dark-grey-text").addClass("grey-text");}
            if (data.zs.text != null){
                $('#zcHead').html(data.zs.lead);
                $('#zcText').html(data.zs.text);
            } else {
                $('#zcHead').html("");
                $('#zcText').html("");
            }

            if (data.ess.status) {$('#hrText').removeClass('grey-text');} else {$('#hrText').addClass('grey-text');}
            $('#hrText').html(data.hr.whole+".<small>"+data.hr.fraction+"</small>");
            $('#hrDim').html(data.hr.dim);

            if (data.ess.status) {$('#difText').removeClass("grey-text").addClass("dark-grey-text");} else {$('#difText').removeClass("dark-grey-text").addClass("grey-text");}
            $('#meText').html(data.dif.whole+"."+data.dif.fraction+data.dif.dim);

            if (data.ess.status) {$('#mtText').removeClass('grey-text');} else {$('#mtText').addClass('grey-text');}
            $('#mtText').html(data.mnc.total);

            if (data.ess.status) {$('#meText').removeClass("grey-text").addClass("dark-grey-text");} else {$('#meText').removeClass("dark-grey-text").addClass("grey-text");}
            $('#meText').html(data.mnc.enabled);

            if (data.ess.status) {$('#acText').removeClass('grey-text');} else {$('#acText').addClass('grey-text');}
            $('#acText').html(data.ac.text);

            if (data.ess.status) {$('#agText').removeClass("grey-text").addClass("darkgrey-text");} else {$('#agText').removeClass("dark-grey-text").addClass("grey-text");}
            $('#agText').html(data.agc.text);

            if (data.ess.status) {$('#txText').removeClass('grey-text');} else {$('#txText').addClass('grey-text');}
            $('#txText').html(data.txc.text);

            if (data.ess.status) {$('#utxoText').removeClass('grey-text');} else {$('#utxoText').addClass('grey-text');}
            $('#utxoText').html(data.utxoc.text);

            if (data.ess.status) {$('#ccText').removeClass('grey-text');} else {$('#ccText').addClass('grey-text');}
            $('#ccText').html(data.mkt.cmc.whole+".<small>"+data.mkt.cmc.fraction+"</small>");

            if (!data.ess.status) {$('#tuText').removeClass("dark-grey-text").addClass("grey-text");} else {$('#tuText').removeClass("grey-text").addClass("dark-grey-text");}
            $('#tuText').html(data.mkt.tsu.text);
            
            if (!data.ess.status) {$('#apText').addClass('grey-text');} else {$('#apText').removeClass('grey-text');}
            $('#apText').html(data.mkt.avp.whole+"."+data.mkt.avp.fraction);

            $('#pcText').removeClass("text-danger text-success grey-text").addClass(data.mkt.cng.class);
            $('#pcText').html(data.mkt.cng.trend+'&nbsp;'+data.mkt.cng.whole+'.'+data.mkt.cng.fraction+'%');

            if ((l.bc != data.bc.text) || (l.circ != data.cs.whole)) {
                l.bc = data.bc.text;l.circ = data.cs.whole;stats = data.csc.data;
                cs_chart.data = stats;cs_chart.invalidateRawData();
            }

            $('#mkTbl').html(data.mtb.tbody);
            $('#saTbl').html(data.sa.tbody);
            $('#raTbl').html(data.ra.tbody);
        }
    });
}

function getMetrics(h,d,r = null) {
  const payload = {
    rId: r,
    pageUrl: location.origin + location.pathname,
    viewport: { w: innerWidth, h: innerHeight },
    dpr: devicePixelRatio || 1,
    vitals: { lcpMs: null, cls: 0, inpMs: null, fcpMs: null },
    ts: Date.now()
  };

  addEventListener("resize", () => {
    payload.viewport.w = innerWidth;
    payload.viewport.h = innerHeight;
  }, { passive: true });

  // Web Vitals (LCP, CLS, INP; FCP optional)
  if ("PerformanceObserver" in window) {
    try {
      new PerformanceObserver((list) => {
        for (const e of list.getEntries()) {
          if (e.name === "first-contentful-paint" && payload.vitals.fcpMs == null) {
            payload.vitals.fcpMs = Math.round(e.startTime);
          }
        }
      }).observe({ type: "paint", buffered: true });
    } catch {}
    try {
      const lcpObs = new PerformanceObserver((list) => {
        const last = list.getEntries().at(-1);
        if (last) payload.vitals.lcpMs = Math.round(last.startTime);
      });
      lcpObs.observe({ type: "largest-contentful-paint", buffered: true });
      addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") lcpObs.disconnect();
      });
    } catch {}
    try {
      const clsObs = new PerformanceObserver((list) => {
        for (const e of list.getEntries()) {
          if (!e.hadRecentInput) payload.vitals.cls += e.value;
        }
      });
      clsObs.observe({ type: "layout-shift", buffered: true });
      addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") clsObs.disconnect();
      });
    } catch {}
    // INP via event timings (worst observed interaction)
    let maxINP = null;
    try {
      const inpObs = new PerformanceObserver((list) => {
        for (const e of list.getEntries()) {
          const d = e.duration;
          if (d != null && (maxINP == null || d > maxINP)) maxINP = d;
        }
      });
      inpObs.observe({ type: "event", buffered: true, durationThreshold: 0 });
      addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") inpObs.disconnect();
      });
    } catch {}
    const finalizeINP = () => {
      if (maxINP != null) payload.vitals.inpMs = Math.round(maxINP);
    };

    // Send exactly once (no keepalive, no beacon, no cookies)
    let sent = false;
    const sendOnce = () => {
      if (sent) return;
      sent = true;
      finalizeINP();
      fetch(h+d, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        // IMPORTANT: no cookies/credentials at all
        credentials: "omit",
        body: JSON.stringify(payload),
        cache: "no-store"
      }).catch(() => {});
    };

    // Fire after onload + small delay (gives LCP time), and also on first hide as a fallback
    addEventListener("load", () => setTimeout(sendOnce, 1500));
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "hidden") sendOnce();
    });
  }
}