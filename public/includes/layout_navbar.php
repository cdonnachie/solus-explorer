<?php
/*
 * Content wrapper, top navbar and the top search bar.
 *
 * Shared layout partial, included by header.php (public) and header-admin.php.
 * Not reachable on its own - see includes/.htaccess.
 */
?>
  <div class="content <?php echo ($ads_on ? 'content-mix' : ''); ?>">
    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-lg scrolling-navbar double-nav">
        <!-- SideNav slide-out button -->
        <div class="float-left">
          <a href="#" data-activates="slide-out" class="button-collapse"><i class="fas fa-bars nav-text"></i></a>
        </div>
        <!-- Breadcrumb-->
        <div class="breadcrumb-dn mr-auto">
          <p id="pageName" class="h4-responsive"><?php if(isset($page_name)&&!empty($page_name)){echo $page_name;} ?></p>
        </div>
        <div class="float-right search-bar-trigger">
          <a id="top-search-show" type="button"> <i class="fas fa-search pointer"></i></a>
        </div>
      <!--
      
      -->
      <div id="topSearch" class="search-bar primary-color px-3">
				<form class="d-flex md-form w-100 justify-content-center" method="GET">
            <input id="inputTopSearch" class="align-self-center search-input form-control card-header-text" type="text" placeholder="  Search for address, block, tx<?php if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){echo ", asset";}?> or address group">
            <button type="submit" class="primary-color align-self-center nav-text pl-2 pr-0 m-0" title="Search"><i class="fas fa-search pointer"></i></button>
            <a type="button" id="tsClose" class="primary-color align-self-center search-bar-close nav-text pl-2 pr-0 m-0" title="Close"><i class="fas fa-times pointer"></i></a>
				</form>
			</div>
      <script  nonce="<?php echo $csp_nonce; ?>">
        $(document).ready(function(){
          $('#top-search-show').on('click', function (e) {
            $('.search-bar').fadeIn();
            e.preventDefault();
          });
          $(document).mouseup(function (e) {
            var container = $('.search-bar form');
            if (!container.is(e.target) && container.has(e.target).length === 0) {
              $('.search-bar').fadeOut();
            }
          });
          $( "#topSearch" ).submit(function( event ) {
            doSearch('<?php echo $settings["explorer_address"];?>', $("#inputTopSearch").val().trim());
          });  
          $("#tsClose").on("click", function(event){
            $('.search-bar').fadeOut();
          });
        })

      </script>
    </nav>

    <!-- /.Navbar -->
  <!-- </header> -->
  <!--Main Navigation-->