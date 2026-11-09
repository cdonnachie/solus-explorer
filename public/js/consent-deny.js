(function(){
  if (window.__tcfapi) return; // don't overwrite a real CMP

  // ---- config: change as needed for tests ----
  const ALLOW_PUBLISHER_STRICTLY_NECESSARY = true; // allow own-site cookies (Purpose 1) only
  const TEST_COOKIE_NAME  = "auth_test";
  const TEST_COOKIE_VALUE = "1";

  // simple listener registry
  const listeners = new Map();
  let nextId = 1;

  // Build tcData so that:
  // - Vendors = no consent (everything denied)
  // - Publisher (you) = Purpose 1 allowed (strictly necessary)
  const purposeConsents = {};                 // vendors: all denied
  const purposeLI       = {};                 // vendors: all denied
  const publisherConsents = {};               // publisher purposes
  const publisherLI       = {};

  if (ALLOW_PUBLISHER_STRICTLY_NECESSARY) {
    // Purpose 1 only for the PUBLISHER (your site)
    publisherConsents["1"] = true;
  }

  const tcData = {
    tcfPolicyVersion: 2,
    // apiVersion is returned via ping
    cmpId: 0,
    cmpVersion: 0,
    gdprApplies: true,
    useNonStandardStacks: false,
    purposeOneTreatment: false,
    isServiceSpecific: true,
    publisherCC: "US",
    // Fake TC string for testing. DO NOT use in prod.
    tcString: "CO_TEST_PUBLISHER_ONLY_P1",
    purpose: {
      consents: purposeConsents,
      legitimateInterests: purposeLI
    },
    vendor: {
      consents: {},                // no vendor consent
      legitimateInterests: {}      // no vendor LI
    },
    // Publisher-only permissions (TCF v2 shape)
    publisher: {
      consents: publisherConsents,           // e.g., {"1": true} means P1 for publisher
      legitimateInterests: publisherLI,
      customPurpose: { consents: {}, legitimateInterests: {} }
    },
    // Optional: restrict vendors for P1 (defensive). Some UIs expose "publisherRestrictions",
    // but for a stub this is enough since vendor consents are already empty/false.
    eventStatus: "tcloaded",
    cmpStatus: "loaded",
    listenerId: undefined
  };

  function safeCallback(cb, value, success){
    try { cb(value, success); } catch(e) { /* ignore */ }
  }

  // Core __tcfapi implementation
  window.__tcfapi = function(command, version, callback, parameter){
    if (typeof callback !== "function") return;

    switch (command) {
      case "ping":
        return safeCallback(callback, {
          gdprApplies: true,
          cmpLoaded: true,
          cmpStatus: "loaded",
          displayStatus: "hidden",
          apiVersion: "2.2"
        }, true);

      case "getTCData": {
        const payload = Object.assign({}, tcData, {
          listenerId: parameter && parameter.listenerId
        });
        return safeCallback(callback, payload, true);
      }

      case "addEventListener": {
        const id = nextId++;
        listeners.set(id, callback);
        const payload = Object.assign({}, tcData, { listenerId: id });
        // Immediately notify current state
        safeCallback(callback, payload, true);
        return; // per spec, callback already invoked
      }

      case "removeEventListener": {
        const id = parameter;
        const ok = listeners.delete(id);
        return safeCallback(callback, ok, ok);
      }

      default:
        return safeCallback(callback, null, false);
    }
  };

  // Minimal postMessage bridge for iframed callers
  window.addEventListener("message", function(ev){
    try{
      const d = ev && ev.data;
      if (!d || typeof d !== "object" || !d.__tcfapiCall) return;
      const { callId, command, version, parameter } = d.__tcfapiCall;
      window.__tcfapi(command, version, function(returnValue, success){
        ev.source && ev.source.postMessage({
          __tcfapiReturn: { returnValue, success, callId }
        }, "*");
      }, parameter);
    }catch(e){}
  });

  // Create the locator iframe (some tags search for it)
  try {
    if (!window.frames["__tcfapiLocator"]) {
      const addLocator = () => {
        if (document.body && !window.frames["__tcfapiLocator"]) {
          const f = document.createElement("iframe");
          f.style.cssText = "display:none";
          f.name = "__tcfapiLocator";
          document.body.appendChild(f);
        }
      };
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", addLocator);
      } else {
        addLocator();
      }
    }
  } catch(e){}

  // ---- Strictly necessary first-party cookie example (for testing) ----
  // NOTE: Real auth/session cookies should be set server-side with HttpOnly+Secure.
  if (ALLOW_PUBLISHER_STRICTLY_NECESSARY) {
    try {
      // Safe defaults for auth-related flag cookie:
      // - Secure (requires HTTPS)
      // - SameSite=Lax (works for top-level navigation)
      // - Path=/ (site-wide)
      var cookie = TEST_COOKIE_NAME + "=" + encodeURIComponent(TEST_COOKIE_VALUE)
                 + "; Path=/; Max-Age=1800; SameSite=Lax";
      if (location.protocol === "https:") cookie += "; Secure";
      document.cookie = cookie;
    } catch(e){}
  }
})();