<?php
    require __DIR__ . '/../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
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
    include_once __DIR__ . '/../../includes/admin_menu.php';
?>
  <script>
    $(".menu-nav").removeClass("active");
    $("#admin-labels").addClass("active");
  </script>
  <style>
    .lbl-chips { min-height: 1.6rem; }
    .lbl-x { background: none; border: 0; color: inherit; font: inherit; line-height: 1; padding: 0 0 0 .4em; cursor: pointer; opacity: .75; }
    .lbl-x:hover, .lbl-x:focus { opacity: 1; outline: 0; }
    .lbl-pfx { font: 500 .75rem/1 SFMono-Regular, Menlo, Monaco, Consolas, monospace; background: #f1f5f8; border: 1px solid #cfdde6; border-radius: 1rem; padding: .3rem .6rem; margin-left: .3rem; cursor: pointer; }
    .lbl-pfx:hover, .lbl-pfx:focus { background: #e4edf3; outline: 0; }
    .lbl-pfx:disabled { opacity: .5; cursor: default; }
    .lbl-facts .row { padding: .5rem 0; border-top: 1px solid rgba(0,0,0,.06); align-items: baseline; }
    .lbl-facts .row:first-child { border-top: 0; }
    .lbl-facts .lbl-k { font-size: .7rem; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: #6c757d; }
    .lbl-id { font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .85rem; overflow-wrap: anywhere; }
    .lbl-num { font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .85rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
    @media (min-width: 768px) { .lbl-facts .lbl-num { text-align: right; } }
    .lbl-msg { font-size: .85rem; padding: .45rem .7rem; margin: 0; }
    .lbl-hint { font-size: .8rem; color: #6c757d; margin: 0; }
    #lblList td, #lblList th { vertical-align: middle; }
    #lblList pre { background: #212529; color: #e9ecef; padding: .75rem 1rem; border-radius: .25rem; font-size: .8rem; white-space: pre; overflow-x: auto; }
  </style>

  <h5 class="pt-4 pb-0">Address labels</h5>
  <p class="lbl-hint mb-3">An alias names one address. Tags describe a whole address group and every address in it.</p>

  <section>
    <div class="card col-12 px-0 mb-4">
      <div class="card-header primary-color card-header-text py-2 d-flex justify-content-between align-items-center"><span class="h6 h6-responsive mb-0"><i class="fas fa-search" aria-hidden="true"></i> ADDRESS OR GROUP</span></div>
      <div class="card-body">
        <form id="lblFind" class="form-row" autocomplete="off">
          <div class="col-12 col-md">
            <label class="sr-only" for="lblId">Address or group id</label>
            <input type="text" id="lblId" class="form-control lbl-id" placeholder="Address or group id" spellcheck="false" maxlength="128">
          </div>
          <div class="col-12 col-md-auto mt-2 mt-md-0">
            <button id="lblShow" type="submit" class="btn btn-info btn-md m-0">Show labels</button>
          </div>
        </form>
        <p id="lblFindErr" class="alert alert-danger lbl-msg mt-3" hidden></p>
        <div id="lblFacts" class="lbl-facts mt-3" hidden></div>
      </div>
    </div>

    <div class="row" id="lblEditors" hidden>
      <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
          <div class="card-header primary-color card-header-text py-2 d-flex justify-content-between align-items-center"><span class="h6 h6-responsive mb-0"><i class="fas fa-tag" aria-hidden="true"></i> ALIAS OF THIS ADDRESS</span><small class="text-white-50 lbl-num" id="lblAliasCount"></small></div>
          <div class="card-body" id="lblAliasBody">
            <div class="lbl-chips mb-3" id="lblAliasChip" aria-live="polite"></div>
            <div class="input-group mb-2">
              <label class="sr-only" for="lblAlias">New alias</label>
              <input type="text" id="lblAlias" class="form-control" placeholder="Set an alias" maxlength="64" spellcheck="false">
              <div class="input-group-append"><button id="lblAliasSet" type="button" class="btn btn-info btn-md m-0">Set</button></div>
            </div>
            <div class="mb-2 lbl-hint">Start with
              <button type="button" class="lbl-pfx primary-text" data-prefix="#" data-target="lblAlias"># mining pool</button>
              <button type="button" class="lbl-pfx primary-text" data-prefix="@" data-target="lblAlias">@ exchange</button>
            </div>
            <p id="lblAliasErr" class="alert alert-danger lbl-msg mb-2" hidden></p>
            <p id="lblAliasWarn" class="alert alert-warning lbl-msg mb-2" hidden></p>
            <p class="lbl-hint">Changes are saved at once. Mining pools: put the <code>#</code> label on the payout address, not the group. A pool's address can sit inside an exchange's group.</p>
          </div>
          <div class="card-body" id="lblAliasOff" hidden>
            <p class="lbl-hint">An alias belongs to one address. Look up an address of this group to set its alias.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
          <div class="card-header primary-color card-header-text py-2 d-flex justify-content-between align-items-center"><span class="h6 h6-responsive mb-0"><i class="fas fa-tags" aria-hidden="true"></i> TAGS OF THE GROUP</span><small class="text-white-50 lbl-num" id="lblTagCount"></small></div>
          <div class="card-body" id="lblTagBody">
            <div class="lbl-chips mb-3" id="lblTagChips" aria-live="polite"></div>
            <div class="input-group mb-2">
              <label class="sr-only" for="lblTag">New tag</label>
              <input type="text" id="lblTag" class="form-control" placeholder="Add a tag" maxlength="64" spellcheck="false">
              <div class="input-group-append"><button id="lblTagAdd" type="button" class="btn btn-info btn-md m-0">Add</button></div>
            </div>
            <div class="mb-2 lbl-hint">Start with
              <button type="button" class="lbl-pfx primary-text" data-prefix="@" data-target="lblTag">@ exchange</button>
            </div>
            <p id="lblTagErr" class="alert alert-danger lbl-msg mb-2" hidden></p>
            <p id="lblTagWarn" class="alert alert-warning lbl-msg mb-2" hidden></p>
            <p class="lbl-hint">Changes are saved at once. Tags apply to every address in the group. When the loader joins two groups, their tags are combined.</p>
          </div>
          <div class="card-body" id="lblTagOff" hidden>
            <p class="lbl-hint">This address has no group yet, so it can't carry group tags.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card col-12 px-0 mb-4">
      <div class="card-header primary-color card-header-text py-2 d-flex justify-content-between align-items-center"><span class="h6 h6-responsive mb-0"><i class="fas fa-list" aria-hidden="true"></i> ALL LABELS</span></div>
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <div class="btn-group btn-group-sm my-1" role="group" aria-label="Label lists">
            <button type="button" class="btn btn-info m-0" id="lblTabAliases" aria-pressed="true">Aliases <span id="lblNA"></span></button>
            <button type="button" class="btn btn-light m-0" id="lblTabTags" aria-pressed="false">Group tags <span id="lblNT"></span></button>
          </div>
          <label class="sr-only" for="lblFilter">Filter labels</label>
          <input type="text" id="lblFilter" class="form-control form-control-sm my-1" style="max-width: 14rem;" placeholder="Filter" spellcheck="false">
        </div>
        <div id="lblList"><p class="lbl-hint">Loading…</p></div>
      </div>
    </div>
    <p class="lbl-hint mb-4">Mining charts and the overview are cached. Label changes appear there after the next metrics run.</p>
  </section>

  <script>
  $(function () {
    var ROOT = <?php echo json_encode(ui_getRootURL(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES); ?>;
    var COIN = <?php echo json_encode((string)($settings["coin_symbol"] ?? ""), JSON_HEX_TAG); ?>;
    var MAX_TAGS = 10;
    var BAD = /[<>"'\u0000-\u001f\u007f-\u009f]/;
    var cur = null, draftAlias = "", draftTags = [], tab = "aliases", list = { aliases: null, tags: null }, pending = null;

    function check(v) {
      if (v === "") return "Enter a label.";
      if (v !== v.trim()) return "Remove the spaces at the start or end.";
      if (BAD.test(v)) return "Labels can't contain < > \" ' or control characters.";
      if (Array.from(v).length > 32) return "Use at most 32 characters.";
      return "";
    }
    function shortId(s) { return s.length > 30 ? s.slice(0, 17) + "…" + s.slice(-6) : s; }
    function link(path, id, text) {
      return $("<a target='_blank' rel='noopener' class='primary-text'>").attr("href", ROOT + path + encodeURIComponent(id)).text(text || id).append(" ↗");
    }
    function chip(text, cls, onRemove, label) {
      var c = $("<span>").addClass("badge " + cls + " mr-1 mb-1").text(text);
      return c.append($("<button type='button' class='lbl-x'>").attr("aria-label", label + " " + text).text("×").on("click", onRemove));
    }
    function msg(el, text) { $(el).text(text || "").prop("hidden", !text); }
    function post(data) { return $.ajax({ url: "labelsup.php", type: "POST", dataType: "json", data: data }); }
    function failed(xhr) { return xhr && xhr.status === 401 ? "Your admin session has ended. Log in again." : "The request failed. Try again."; }
    function toast(text) { if (window.toastr) { toastr.success(text); } }

    // ---- lookup and facts ----
    function factRow(label, idCell, count, balance) {
      var r = $("<div class='row no-gutters'>");
      r.append($("<div class='col-12 col-md-2 lbl-k'>").text(label));
      r.append($("<div class='col-12 col-md lbl-id pr-md-3'>").append(idCell));
      r.append($("<div class='col-6 col-md-2 lbl-num text-muted'>").text(count));
      r.append($("<div class='col-6 col-md-3 lbl-num text-right'>").text(balance));
      return r;
    }
    function showFacts() {
      var f = $("#lblFacts").empty().prop("hidden", false);
      if (cur.kind === "address") {
        f.append(factRow("Address", link("/address/?address=", cur.address.id), "", cur.address.balance + " " + COIN));
      }
      var g = cur.kind === "group" ? cur.group : cur.address.group;
      if (g) {
        f.append(factRow("Group", link("/addressgroup/?groupid=", g.id), g.count.toLocaleString("en-US") + " addresses", g.balance + " " + COIN));
      }
    }
    function load(res) {
      cur = res;
      draftAlias = res.kind === "address" ? res.address.alias : "";
      var g = res.kind === "group" ? res.group : res.address.group;
      draftTags = g ? g.tags.slice() : [];
      $("#lblId").val(res.kind === "address" ? res.address.id : res.group.id);
      showFacts();
      $("#lblEditors").prop("hidden", false);
      $("#lblAliasBody").prop("hidden", res.kind !== "address");
      $("#lblAliasOff").prop("hidden", res.kind === "address");
      $("#lblAliasCount").prop("hidden", res.kind !== "address");
      $("#lblTagBody").prop("hidden", !g);
      $("#lblTagOff").prop("hidden", !!g);
      $("#lblAlias, #lblTag").val("");
      ["#lblAliasErr", "#lblAliasWarn", "#lblTagErr", "#lblTagWarn"].forEach(function (s) { msg(s, ""); });
      renderAlias(); renderTags();
    }
    function lookup(id) {
      msg("#lblFindErr", "");
      $("#lblShow").prop("disabled", true);
      post({ action: "lookup", id: id }).done(function (res) {
        if (res.status === "ok") { load(res); }
        else { msg("#lblFindErr", res.error); }
      }).fail(function (xhr) { msg("#lblFindErr", failed(xhr)); })
        .always(function () { $("#lblShow").prop("disabled", false); });
    }
    $("#lblFind").on("submit", function (e) { e.preventDefault(); lookup($.trim($("#lblId").val())); });

    // ---- alias and tags: every change is stored at once ----
    function busy(on) { $("#lblEditors").find("button, input").prop("disabled", on); if (!on) renderTags(); }
    function saveAlias(value) {
      busy(true);
      post({ action: "alias", id: cur.address.id, alias: value }).done(function (res) {
        if (res.status === "ok") { load(res); toast(res.message); refreshList(); }
        else { msg("#lblAliasErr", res.error); }
      }).fail(function (xhr) { msg("#lblAliasErr", failed(xhr)); })
        .always(function () { busy(false); });
    }
    function saveTags(tags) {
      var g = cur.kind === "group" ? cur.group : cur.address.group;
      busy(true);
      post({ action: "tags", id: g.id, tags: JSON.stringify(tags) }).done(function (res) {
        if (res.status === "ok") {
          if (cur.kind === "address") { cur.address.group = res.group; load(cur); } else { load(res); }
          toast(res.message); refreshList();
        } else { msg("#lblTagErr", res.error); }
      }).fail(function (xhr) { msg("#lblTagErr", failed(xhr)); })
        .always(function () { busy(false); });
    }

    function renderAlias() {
      var box = $("#lblAliasChip").empty();
      if (draftAlias) { box.append(chip(draftAlias, "badge-info", function () { saveAlias(""); }, "Remove alias")); }
      else { box.append($("<span class='lbl-hint'>").text("No alias yet.")); }
      $("#lblAliasCount").text((draftAlias ? 1 : 0) + " / 1");
      $("#lblAliasSet").text(draftAlias ? "Replace" : "Set");
    }
    function aliasInput() {
      var v = $("#lblAlias").val(), e = v ? check(v) : "";
      $("#lblAlias").toggleClass("is-invalid", !!e);
      msg("#lblAliasErr", e);
      msg("#lblAliasWarn", !e && v.charAt(0) === "@" ? "Exchanges are usually tagged on the group. An alias is fine if you only want to name this deposit address." : "");
      return !e && !!v;
    }
    function setAlias() { if (aliasInput()) { saveAlias($("#lblAlias").val()); } }
    $("#lblAlias").on("input", aliasInput).on("keydown", function (e) { if (e.key === "Enter") { e.preventDefault(); setAlias(); } });
    $("#lblAliasSet").on("click", setAlias);

    function renderTags() {
      var box = $("#lblTagChips").empty();
      draftTags.forEach(function (t, i) {
        box.append(chip(t, "badge-primary", function () { saveTags(draftTags.filter(function (x, j) { return j !== i; })); }, "Remove tag"));
      });
      if (!draftTags.length) { box.append($("<span class='lbl-hint'>").text("No tags yet.")); }
      $("#lblTagCount").text(draftTags.length + " / " + MAX_TAGS);
      $("#lblTagAdd").prop("disabled", draftTags.length >= MAX_TAGS);
    }
    function tagInput() {
      var v = $("#lblTag").val(), e = v ? check(v) : "";
      if (!e && v && draftTags.some(function (t) { return t.toLowerCase() === v.toLowerCase(); })) e = "This group already has that tag.";
      $("#lblTag").toggleClass("is-invalid", !!e);
      msg("#lblTagErr", e);
      msg("#lblTagWarn", !e && v.charAt(0) === "#" ? "Pool labels usually go on the payout address alias, since a pool's address can sit inside an exchange's group. You can still add it here." : "");
      return !e && !!v;
    }
    function addTag() { if (tagInput() && draftTags.length < MAX_TAGS) { saveTags(draftTags.concat([$("#lblTag").val()])); } }
    $("#lblTag").on("input", tagInput).on("keydown", function (e) { if (e.key === "Enter") { e.preventDefault(); addTag(); } });
    $("#lblTagAdd").on("click", addTag);

    $(".lbl-pfx").on("click", function () {
      var t = $("#" + $(this).data("target")), v = t.val().replace(/^[#@]/, "");
      t.val($(this).data("prefix") + v).trigger("focus").trigger("input");
    });

    // ---- list ----
    function indexHelp() {
      var cmds = [];
      if (!list.indexes.alias) cmds.push('db.ADDRESSES.createIndex({ alias: 1 },\n  { name: "alias_labels", partialFilterExpression: { alias: { $type: "string" } } })');
      if (!list.indexes.tags) cmds.push('db.ADDRESSGROUPS.createIndex({ tags: 1 },\n  { name: "tags_labels", partialFilterExpression: { tags: { $exists: true } } })');
      return $("<div>").append(
        $("<p class='alert alert-warning lbl-msg mb-3'>").text("This list needs an index that the database doesn't have yet. Create it once in mongosh on the explorer's database. The build runs in the background and doesn't stop the loader; on a large chain it takes a few minutes."),
        $("<pre>").text(cmds.join("\n")),
        $("<p class='lbl-hint'>").text("Looking up and editing labels above works without it. See docs/operations.md, “Address labels”."));
    }
    function renderList() {
      var body = $("#lblList").empty(), rows = tab === "aliases" ? list.aliases : list.tags;
      $("#lblNA").text(list.aliases ? list.aliases.length : "");
      $("#lblNT").text(list.tags ? list.tags.length : "");
      if (!list.indexes) { body.append($("<p class='lbl-hint'>").text("Loading…")); return; }
      if (rows === null) { body.append(indexHelp()); return; }
      var q = $.trim($("#lblFilter").val()).toLowerCase();
      var key = tab === "aliases" ? "alias" : "tag";
      var shown = rows.filter(function (r) { return !q || r[key].toLowerCase().indexOf(q) >= 0 || r.id.toLowerCase().indexOf(q) >= 0; });
      if (!rows.length) { body.append($("<p class='lbl-hint'>").text(tab === "aliases" ? "No address has an alias yet." : "No group has tags yet.")); return; }
      if (!shown.length) { body.append($("<p class='lbl-hint'>").text("No labels match “" + q + "”.")); return; }
      var t = $("<table class='table table-sm mb-0'>"), tb = $("<tbody>");
      t.append($("<thead>").append($("<tr>").append(
        $("<th>").text(tab === "aliases" ? "Alias" : "Tag"), $("<th>").text(tab === "aliases" ? "Address" : "Group"),
        tab === "aliases" ? $("<th>") : $("<th class='text-right'>").text("Addresses"),
        $("<th class='text-right'>").text("Balance (" + COIN + ")"), $("<th>"))));
      shown.forEach(function (r, i) {
        var k = tab + ":" + r.id + ":" + r[key];
        var idCell = tab === "aliases" ? link("/address/?address=", r.id) : link("/addressgroup/?groupid=", r.id, shortId(r.id)).attr("title", r.id);
        var act = $("<td class='text-right text-nowrap'>");
        if (pending === k) {
          act.append($("<span class='text-danger small mr-2'>").text("Remove " + r[key] + "?"),
            $("<button type='button' class='btn btn-sm btn-danger m-0 mr-1'>").text("Remove").on("click", function () { removeRow(r); }),
            $("<button type='button' class='btn btn-sm btn-light m-0'>").text("Keep").on("click", function () { pending = null; renderList(); }));
        } else {
          act.append($("<button type='button' class='btn btn-sm btn-info m-0 mr-1'>").text("Edit").on("click", function () {
              lookup(r.id); $("html, body").animate({ scrollTop: $("#lblFind").offset().top - 80 }, 200); }),
            $("<button type='button' class='btn btn-sm btn-link text-danger m-0'>").text("Remove").on("click", function () { pending = k; renderList(); }));
        }
        tb.append($("<tr>").append(
          $("<td>").append($("<span>").addClass("badge mb-1 " + (tab === "aliases" ? "badge-info" : "badge-primary")).text(r[key])),
          $("<td class='lbl-id'>").append(idCell),
          $("<td class='lbl-num text-right text-muted'>").text(tab === "aliases" ? "" : r.count.toLocaleString("en-US")),
          $("<td class='lbl-num text-right'>").text(r.balance), act));
      });
      body.append($("<div class='table-responsive'>").append(t.append(tb)));
      if (rows.length >= list.limit) { body.append($("<p class='lbl-hint mt-2'>").text("Showing the first " + list.limit + " groups or addresses. Use the filter or look up an id above.")); }
    }
    function removeRow(r) {
      pending = null;
      var req = tab === "aliases"
        ? post({ action: "alias", id: r.id, alias: "" })
        : post({ action: "lookup", id: r.id }).then(function (res) {
            if (res.status !== "ok") return res;
            var left = res.group.tags.filter(function (t) { return t !== r.tag; });
            return post({ action: "tags", id: r.id, tags: JSON.stringify(left) });
          });
      req.done(function (res) {
        if (res.status === "ok") {
          toast(tab === "aliases" ? "Alias " + r.alias + " removed" : "Tag " + r.tag + " removed");
          if (cur) { lookup(cur.kind === "address" ? cur.address.id : cur.group.id); }
          refreshList();
        } else { $("#lblList").prepend($("<p class='alert alert-danger lbl-msg mb-2'>").text(res.error)); }
      }).fail(function (xhr) { $("#lblList").prepend($("<p class='alert alert-danger lbl-msg mb-2'>").text(failed(xhr))); });
    }
    function refreshList() {
      post({ action: "list" }).done(function (res) {
        if (res.status !== "ok") { $("#lblList").empty().append($("<p class='alert alert-danger lbl-msg'>").text(res.error)); return; }
        list = res; renderList();
      }).fail(function (xhr) { $("#lblList").empty().append($("<p class='alert alert-danger lbl-msg'>").text(failed(xhr))); });
    }
    function setTab(t) {
      tab = t; pending = null;
      $("#lblTabAliases").toggleClass("btn-info", t === "aliases").toggleClass("btn-light", t !== "aliases").attr("aria-pressed", t === "aliases");
      $("#lblTabTags").toggleClass("btn-info", t === "tags").toggleClass("btn-light", t !== "tags").attr("aria-pressed", t === "tags");
      renderList();
    }
    $("#lblTabAliases").on("click", function (e) { e.preventDefault(); setTab("aliases"); });
    $("#lblTabTags").on("click", function (e) { e.preventDefault(); setTab("tags"); });
    $("#lblFilter").on("input", renderList);

    refreshList();
  });
  </script>
<?php
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"admin/labels",(int)$apt->getDurationMicroSeconds("total"));
endif; ?>
