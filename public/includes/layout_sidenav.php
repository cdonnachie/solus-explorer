<?php
/*
 * Side navigation: logo, search, page links, socials, banners, badges.
 *
 * Shared layout partial, included by header.php (public) and header-admin.php.
 * Not reachable on its own - see includes/.htaccess.
 */
?>
  <!--Main Navigation-->
  <!-- <header> -->

    <!-- Sidebar navigation -->
    <div id="slide-out" class="side-nav fixed wide sn-bg-4">
      <div class="side-nav-content <?php if ($tp_on){ echo "side-nav-content-tp";} ?>">
        <ul class="custom-scrollbar mb-0">
          <!-- Logo -->
          <li class="logo-sn">
            <div class="ml-3 mr-3 text-center">
              <a href="<?php echo $settings["explorer_address"];?>/" class="pl-0 logo">
                <img src="<?php echo $settings["explorer_address"];?>/img/<?php if(array_key_exists("logo_image",$settings)){ echo $settings['logo_image'];}else{echo 'logo.svg';}?>" 
                  height="<?php echo $settings['logo_height']; ?>" 
                  width="<?php echo $settings['logo_width']; ?>" 
                  alt="<?php echo ui_getCoinName(); ?> explorer logo"
                  loading="lazy">
              </a>
            </div>
          </li>
          <!--/. Logo -->

          <!--Search Form-->
          <li>
            <form class="search-form" role="search" id="searchForm">
              <div class="form-group md-form mt-0 pt-1">
                <input type="text" class="form-control text-left" placeholder="Search" id="inputSearch">
              </div>
            </form>
          </li>
          <script type="text/javascript" nonce="<?php echo $csp_nonce;?>">
            $( document ).ready(function() {
              $( "#searchForm" ).submit(function( event ) {
                doSearch('<?php echo $settings["explorer_address"];?>', $("#inputSearch").val().trim());
              });  
            });
 
          </script>

          <!--/.Search Form-->
          <!-- Side navigation links -->
          <li>
            <ul class="collapsible collapsible-accordion pb-2">
              <li class="nav-text"> <a id="Overview" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/"><i class="fas fa-tachometer-alt wrem-2"></i>
                  Overview</a>
              </li>
              <li class="nav-text"> <a id="Blocks" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/blocks/"><i class="fas fa-cubes wrem-2"></i>
                  Blocks</a>
              </li>
              <li class="nav-text"> <a id="Coins" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/groups/"><i class="fas fa-coins wrem-2"></i>
                  Coin Distribution</a>
              </li>
              <?php if (isset($settings["show_pages"]["airdrop"]) && $settings["show_pages"]["airdrop"]) : ?>
              <li class="nav-text"> <a id="Airdrop" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/airdrop/"><i class="fas fa-hand-holding-water wrem-2"></i>
                  Airdrop</a>
              </li>
              <?php endif; ?>  
              <?php if (isset($settings['show_pages']['addresses']) && $settings['show_pages']['addresses']) :?>
              <li class="nav-text"> <a id="Addresses" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/addresses/"><i class="fas fa-at wrem-2"></i>
                  Top Addresses</a>
              </li>
              <?php endif; ?>
              <li class="nav-text"> <a id="Movements" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/movements/"><i class="fas fa-exchange-alt wrem-2"></i>
                  Coin Movements</a>
              </li>
              <?php if (isset($settings["show_pages"]["burned"]) && $settings["show_pages"]["burned"]) : ?>
                <li class="nav-text"> <a id="Burned" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/burnedcoins/"><i class="fas fa-fire-alt wrem-2"></i>
                  Burned Coins</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings["show_pages"]["assets"]) && $settings["show_pages"]["assets"]) : ?>
                <li class="nav-text"> <a id="Assets" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/assets/"><i class="fas fa-box-open wrem-2"></i>
                  Assets</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
              <li class="nav-text"> <a id="Masternodes" class="collapsible-header" href="<?php echo $settings["explorer_address"]; ?>/masternodes/"><i class="fab fa-hubspot wrem-2"></i>
                  Masternodes</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['mining']) && $settings['show_pages']['mining']) : ?>
              <li class="nav-text"> <a id="Mining" class="collapsible-header" href="<?php echo$settings["explorer_address"];?>/mining/"><i class="fas icons-pike wrem-2"></i>
                  Mining</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['staking']) && $settings['show_pages']['staking']):?>
              <li class="nav-text"> <a id="Staking" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/staking/"><i class="fas fa-tint wrem-2"></i>
                  Staking</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['goldrush']) && $settings['show_pages']['goldrush'] && shadow_enabled()):?>
              <li class="nav-text"> <a id="GoldRush" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/goldrush/"><i class="fas fa-bolt wrem-2"></i>
                  Gold Rush</a>
              </li>
              <?php endif; ?>
              <li class="nav-text"> <a id="Network" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/network/"><i class="fas fa-network-wired wrem-2"></i>
                  Network</a>
              </li>
              <?php if ((isset($settings['show_pages']['markets']) && $settings['show_pages']['markets']) || !isset($settings['show_pages']['markets']) ) :  ?>
              <li class="nav-text"> <a id="Markets" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/markets/"><i class="fas fa-chart-line wrem-2"></i>
                  Markets</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings["show_pages"]["stats"]) && $settings["show_pages"]["stats"]) : ?>
                <li class="nav-text"> <a id="Stats" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/stats/"><i class="fas fa-chart-area wrem-2"></i>
                  Chain Statistics</a>
              </li>
              <?php endif; ?>
              <?php '
              // <!-- 
              <li class="nav-text"> <a id="Daemon" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/daemon/"><i class="fas fa-cog wrem-2"></i>
                  Daemon Information</a>
              </li> --> 
              '?>
              <?php 
                if (isset($settings["custom_links"]) && count($settings["custom_links"])>0){
                  echo "<hr>";
                  foreach ($settings["custom_links"] as $link){
                    echo '<li class="nav-text"> <a class="collapsible-header" href="'.$link["href"].'" target="_blank" rel="noopener"><i class="'.$link["icon"].' wrem-2"></i>'.$link["text"].'</a>
                    </li>';
                  }
                }
              ?>

              <hr>
              <li class="nav-text"> <a id="Api" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/api/"><i class="fas fa-code-branch wrem-2"></i>
                API</a>
              </li>
              <?php if (isset($settings['show_pages']['about']) && $settings['show_pages']['about']) : ?>
              <li class="nav-text"> <a id="About" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/about/"><i class="fas fa-info-circle wrem-2"></i>
                About</a>
              </li> 
              <?php endif; ?>
              

            </ul>
            <?php 
                if (isset($settings["show_socials"]) && $settings["show_socials"] && isset($settings["social_buttons"]) && count($settings["social_buttons"])>0) {
                echo "<hr>";
                echo "<div class='row justify-content-center ml-0 mr-1'>";
                  foreach ($settings["social_buttons"] as $btn){
                    echo '<span class="p-0 m-0"><a class="collapsible-header nav-text btn-social" href="'.$btn['href'].'" title="'.$btn['title'].'" target="_blank" rel="noopener"><i class="'.$btn['icon'].'"></i></a></span>';
                  }
                
                echo "</div>";
                }
              ?>
          </li>
          <!--/. Side navigation links -->
        </ul>
          <?php 
            if (isset($settings["menu_banners"]) && count($settings["menu_banners"])>0){
              //echo "<hr>";
              foreach ($settings["menu_banners"] as $banner){ ?>
                <div class="m-0 text-center p-0 w-100">
                  <a href="<?php echo $banner["href"];?>" title="<?php echo $banner["title"];?>" class="p-0 m-0"
                    style="height:inherit!important;line-height:inherit!important;" target="_blank">
                    <img class="p-0 m-0" 
                      height="<?php echo (!empty($banner["height"])?$banner["height"]:"")?>" 
                      width="<?php echo (!empty($banner["width"])?$banner["width"]:"")?>" 
                      src="<?php echo $settings["explorer_address"].$banner["img"];?>">
                  </a>
                </div>
                  
              <?php }
            }
          ?>
      </div>
        
      <div class="side-nav-footer <?php if ($tp_on){ echo "side-nav-footer-tp"; } ?>"> 
        <?php if ($tp_on) : ?>
          <div class="trustpilot-widget" data-locale="en-US" data-template-id="<?php echo htmlspecialchars($tp["template_id"]); ?>" data-businessunit-id="<?php echo htmlspecialchars($tp["business_id"]); ?>" data-style-height="52px" data-style-width="100%"<?php if (!empty($tp["token"])) { echo ' data-token="'.htmlspecialchars($tp["token"]).'"'; } ?>>
            <a href="<?php echo htmlspecialchars($tp["review_url"] ?? "https://www.trustpilot.com/"); ?>" target="_blank" rel="noopener">Trustpilot</a>
          </div>
        <?php endif; ?>
        <?php if (!empty($settings["operator_name"]) || !empty($settings["operator_logo"])) : ?>
          <div class="row align-items-end">
            <?php ui_RenderMenuBadge($settings["operator_label"] ?? "Operated by", $settings["operator_name"] ?? "", $settings["operator_url"] ?? "", $settings["operator_logo"] ?? "", "25px", "col-12 pr-0 mr-0"); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($settings["show_menu_reflinks"]) && (!empty($settings["hosting_ref"]) || !empty($settings["wallet_ref"]))) : ?>
        <div class="row align-items-end">
          <?php foreach (["hosting_ref" => "col-6 pr-0 mr-0", "wallet_ref" => "col-6 pl-2 ml-0"] as $ref_key => $ref_col) {
            $ref = $settings[$ref_key] ?? [];
            if (!empty($ref)) {
              ui_RenderMenuBadge($ref["text"] ?? "", $ref["name"] ?? "", $ref["href"] ?? "", $ref["img_src"] ?? "", "18px", $ref_col);
            }
          } ?>
        </div>
        <?php endif; ?>          
      </div> 

      <div class="sidenav-bg mask-strong"></div>
    </div>
    <!--/. Sidebar navigation -->

