<?php
/*
 * Closing content wrapper, search-not-found modal and copyright.
 *
 * Shared layout partial, included by footer.php (public) and footer-admin.php.
 * Not reachable on its own - see includes/.htaccess.
 */
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


