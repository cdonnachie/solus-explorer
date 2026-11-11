<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

 $result = array();


 $db_bc = db_getLastDbBlockNumber();
 $rpc_bc = getRpcBlockCount();
 if ($db_bc < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }
 $lbk = db_getLastBlocks(1);
 if (count($lbk)>0){
   $etime = $lbk[0]->block_time_sec;
 } else {
   $etime = time();
 }
 $bks = db_getBlocksByTimeRange($etime-86400, $etime, true, true, true);
 if (count($bks)>0){
   $lmr = splitFloat($bks[count($bks)-1]->pow_reward, $precision, $precision);
   $lbr = $bks[count($bks)-1]->block_reward;
   $lbr_split = splitFloat($lbr, $precision, $precision);
   $bc = count($bks);
   $amr = 0; // average mining reward
   $b_ix = array();
   $b_br = array(); //block_reward
   $b_mr = array(); //pow_reward
   $n_ix = array(); //non-pow blocks
   $n_mr = array(); //non-pow rewards

   $miners_blocks = array();
   $tags_blocks=array();
   $blocks_total = 0;
   $miners_coins = array();
   $tags_coins = array();
   $coins_total = 0;
   $winners_tags = array();
   $ama_dec = @cdec(0);
   $aba_dec = @cdec(0);
   $nba_dec = @cdec(0);

   foreach ($bks as $br){
     array_push($b_br, @cdec($br->block_reward));
     if($br->type == 'PoW'){
       array_push($b_ix, $br->block_ix);
       array_push($b_mr, @cdec($br->pow_reward));
       $ama_dec = $ama_dec + @cdec($br->pow_reward);
       $aba_dec = $aba_dec + @cdec($br->block_reward);
       $m_tag = $br->pow_winner;
       if (!array_key_exists($br->pow_winner, $winners_tags)){
         $grp = db_getAddressTags($br->pow_winner);
         if ($grp->has_tags && $grp->group_tags[0]!="") {
          $m_tag = $grp->group_tags[0];
          $winners_tags[$br->pow_winner] = $m_tag;
         }
         if ($grp->has_alias) {
           $m_tag = $grp->address_alias;
           $winners_tags[$br->pow_winner] = $m_tag;
         }
       } else {
         $m_tag = $winners_tags[$br->pow_winner];
       }

       array_key_exists($br->pow_winner,$miners_blocks) ? $miners_blocks[$br->pow_winner] += 1 : $miners_blocks[$br->pow_winner] = 1;
       array_key_exists($m_tag, $tags_blocks) ? $tags_blocks[$m_tag]+=1 : $tags_blocks[$m_tag] = 1  ;
       $blocks_total += 1;
       array_key_exists($br->pow_winner,$miners_coins) ? $miners_coins[$br->pow_winner] += @cdec($br->pow_reward) : $miners_coins[$br->pow_winner] = @cdec($br->pow_reward);
       array_key_exists($m_tag, $tags_coins) ? $tags_coins[$m_tag] += @cdec($br->pow_reward) : $tags_coins[$m_tag] = @cdec($br->pow_reward);
       $coins_total += @cdec($br->pow_reward);
       
     }else{ 
       array_push($n_ix, $br->block_ix);
       array_push($n_mr, $br->block_reward);
       $nba_dec += @cdec($br->block_reward);
     }
   }
   $ama = array_filter($b_mr);
   $amr = count($b_mr)>0 ? splitFloat($ama_dec/count($b_mr),$precision, $precision) : splitFloat(0,$precision, $precision);
   $aba = array_filter($b_br);
   $abr = (count($b_ix)+count($n_ix))>0 ?  splitFloat(($aba_dec + $nba_dec)/(count($b_ix)+count($n_ix)),$precision, $precision) :  splitFloat(0,$precision, $precision);

   
   arsort($miners_blocks);
   arsort($miners_coins);
   arsort($tags_blocks);
   arsort($tags_coins);

   $blocks_data = array();
   if (count($tags_blocks)<10){$n = count($tags_blocks);} else {$n = 9;}
   $i = 0; $t_blocks = 0;
   foreach ($tags_blocks as $name=>$blocks){
     if ($i>=$n) {break;}
     $blocks_data[$name] = $blocks;
     $t_blocks += $blocks;
     $i++;
   }
   $blocks_data['Other'] = $blocks_total-$t_blocks;

   $coins_data = array();
   if (count($tags_coins)<10){$n = count($tags_coins);} else {$n = 9;}
   $i = 0; $t_coins = 0;
   foreach ($tags_coins as $name=>$coins){
     if ($i>=$n) {break;}
     $coins_data[$name] = ($coins);
     $t_coins += ($coins);
     $i++;
   }
   $coins_data['Other'] = dflt($coins_total-$t_coins);
 }

 $rpc_nhr = getNetworkHashrate();
 $nhr_split = splitFloat($rpc_nhr->value,3,3);
 $rpc_nd = getDifficulty();
 $nd_split = splitFloat($rpc_nd->value,3,3);

 $gma = getChainMetricsAggregates(time(),168);


// last pow reward
    $result['lmr']['text'] = $lmr->whole.".<small>".$lmr->fraction.'</small>';
    $result['lbr']['text'] = $lbr_split->whole.".".$lbr_split->fraction;
// avg pow reward
    $result['amr']['text'] = $amr->whole.".<small>".$amr->fraction.'</small>';
    $result['abr']['text'] = $abr->whole.".".$abr->fraction;
// hashrate
    $result['hr']['text'] = $nhr_split->whole.".<small>".$nhr_split->fraction."</small>";
    $result['hr']['dim'] = $rpc_nhr->dimension;

// difficulty
    $result['dif']['text'] = $nd_split->whole.".<small>".$nd_split->fraction."</small>";
    if (isset($rpc_nd->dimension) && !empty($rpc_nd->dimension)) ($rpc_nd->dimension="(".$rpc_nd->dimension.")");
    $result['dif']['dim'] = $rpc_nd->dimension;
    
// pow rewards chart
    $result['mrc']['pwb'] = array();
    $result['mrc']['npwb'] = array();
    for ($i=0; $i<count($b_ix); $i++){
        array_push($result['mrc']['pwb'], array("x" => ($b_ix[$i]), "y" => ($b_mr[$i])));
      }
    for ($i=0; $i<count($n_ix); $i++){
        array_push($result['mrc']['npwb'], array("x" => ($n_ix[$i]), "y" => ($n_mr[$i])));
      }

// top miners pie chart
    $result['tmc']['labels'] = array_keys($blocks_data);
    $result['tmc']['data'] = array_values($blocks_data);
    $result['tmc']['bgc'] = getChartColors(count($blocks_data)-1,1,true);
    $result['tmc']['hbc'] = getChartColors(count($blocks_data)-1,1,true);

// top miners datatable
    $result['tmt']['data'] = array();
    foreach ($miners_coins as $name=>$coins) {
        $row=array();
        if ($name != "Other"){
            $c_split=splitFloat($coins, $precision, $precision);
            $tag = ui_labelBadge($winners_tags[$name] ?? '', 'badge-info ml-1');
            array_push($row, "<a class='txlist-txid text-primary' href='".$settings["explorer_address"]."/address/?address=".$name."'>".$name."</a>".$tag);
            array_push($row, "<span class='blocklist-amount primary-text'>".$miners_blocks[$name]."</span>");
            array_push($row, $c_split->whole.".<small>".$c_split->fraction."</small>");
            array_push($result['tmt']['data'], $row);
        }
    }


// hash/diff chart
    $result['hdc']['labels'] = array_values($gma->timestamps);
    $result['hdc']['hashrate'] = array();
    $result['hdc']['difficulty'] = array();
    foreach(array_values($gma->hashrate) as $hr){
       array_push($result['hdc']['hashrate'],(string)$hr);
       //array_push($result['hdc']['hashrate'],random_int(350000, 248984232));
    }
    foreach(array_values($gma->difficulty) as $dif){
        array_push($result['hdc']['difficulty'],(string)$dif);
     }

    header('Content-Type: application/json');
    echo json_encode($result);

?>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_up/mining",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>