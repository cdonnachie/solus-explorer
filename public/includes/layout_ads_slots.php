<?php
/*
 * Footer ad slots: the render helper and the five slot containers.
 *
 * Public pages only; footer-admin.php never includes this file. React/Astro must
 * not own these containers - the provider replaces their contents at runtime.
 */
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
<?php if ($ads_on) : ?>
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
<?php endif; ?>