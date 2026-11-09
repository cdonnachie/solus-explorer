<?php
include_once "explorerDbMongo.php";
use Decimal\Decimal;


/**
 * Vout addresses that are parsed but deliberately never stored.
 *
 * Carrier outputs hold no spendable value, so the VOUTS collection skips
 * them. Single-sourced because both the writer (db_insertVouts) and the
 * loader's post-write verification have to agree on the set: when they
 * disagreed, verification rejected every block containing an OP_RETURN.
 */
    function db_unstoredVoutAddresses(){
        return ["nulldata", "nullassetdata", "Shield", "Credit Pool"];
    }

// --- Database Connection ---
    function db_initConnection(){
        global $settings;
        if (isset($settings['db_type']) && $settings['db_type'] != 'mongodb'){
            if (PHP_SAPI != 'cli'){
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
            }
            print("Unsupported db_type '".$settings['db_type']."': Solus Explorer supports MongoDB only. ".
                  "Set \"db_type\" => \"mongodb\" in settings.php.".PHP_EOL);
            exit(1);
        }
        return db_initMongoConnection();
    }
    function db_initMongoConnection(){
        global $settings;
        if (isset($settings['db_opts']) && !empty($settings['db_opts']) ){
            return new MongoDB\Driver\Manager($settings['db_protocol']."://".$settings['db_user'].":".$settings['db_pass']."@".$settings['db_host'].":".$settings['db_port']."/".$settings['db_name'],$settings['db_opts']);
        } else {
            return new MongoDB\Driver\Manager($settings['db_protocol']."://".$settings['db_user'].":".$settings['db_pass']."@".$settings['db_host'].":".$settings['db_port']."/".$settings['db_name']);
        }
    }


    function db_checkDbConnected(){
        return mongo_checkDbConnected();
    }
// --- Collections ---
    function db_checkCollections($dbm, $db_name)
    {
        return mongo_checkCollections($dbm, $db_name);

    }
    
// --- DB Queries ---
  // >>> General Queries <<<

    function db_listDbCollections()
    {
        return mongo_listDbCollections();
    }
    function db_countDbCollection($collection)
    {
        return mongo_countDbCollection($collection);
    }
    function db_countDbCollectionQuery($collection, $query)
    {
        return mongo_countDbCollectionQuery($collection, $query);
    }
    // alias to db_sizeDbCollection
    function db_getDbCollectionStats($collection){
        return db_sizeDbCollection($collection);
    }

    function db_sizeDbCollection($collection)
    {
        return mongo_sizeDbCollection($collection);
    }
    function db_sizeDb()
    {
        return mongo_sizeDb();
    }
    function db_getDbVersion()
    {
        return mongo_getDbVersion();
    }
    function db_getDbCoinSupply()
    {
        return mongo_getDbCoinSupply();
    }
  // >>> Block Queries <<<
    function db_getLastDbBlockNumber()
    {
        return mongo_getLastDbBlockNumber();
    }
    function db_getBlockByHash($block_hash)
    {
        return mongo_getBlockByHash($block_hash);
    }
    function db_getBlockByHeight($block_ix)
    {
        return mongo_getBlockByHeight($block_ix);
    }
    function db_getLastBlocks($limit = 100, $skip = 0, $filter = null)  // SIP-061 added filter
    {
        global $settings;
        return mongo_getLastBlocks($limit, $skip, $filter);
    }
    function db_getBlocksByTimeRange($start_time, $end_time, $sort_asc = false, $gte_start = false, $lte_end = true)
    {
        return mongo_getBlocksByTimeRange($start_time, $end_time, $sort_asc, $gte_start, $lte_end);
    }
    function db_countBlocksByTimeRange($start_time, $end_time, $gte_start = false, $lte_end = true)
    {
        return mongo_countBlocksByTimeRange($start_time, $end_time,$gte_start, $lte_end);
    }
    function db_getBlockRewardSumByTimeRange($start_time, $end_time, $gte_start = false, $lte_end = true)
    {
        return mongo_getBlockRewardSumByTimeRange($start_time, $end_time, $gte_start, $lte_end);
    }

  // >>> TX Queries <<<
    function db_getTxCount($since = null) 
    {
        return mongo_getTxCount($since) ;
    }
    function db_getTxsByBlockHeight($block_ix, $sort = [])
    {
        return mongo_getTxsByBlockHeight($block_ix, $sort) ;
    }
    function db_getTxByHash($tx_id)
    {
        return mongo_getTxByHash($tx_id);
    }
    function db_getTxCountByBlockIx($block_ix)
    {
        return mongo_getTxCountByBlockIx($block_ix) ;
    }
  // >>> VOUT Queries <<<
    function db_getVoutData($vout_id)
    {
        return mongo_getVoutData($vout_id) ;
    }
    function db_getVoutCountByBlockIx($block_ix)
    {
        return mongo_getVoutCountByBlockIx($block_ix) ;
    }
    function db_getVoutByIdMany($ids)
    {
        return mongo_getVoutByIdMany($ids) ;
    }
    function db_getAirdropByIdMany($ids)
    {
        return mongo_getAirdropByIdMany($ids) ;
    }
  // >>> Address Queries <<<
    function db_getAddressCount() 
    {
        return mongo_getAddressCount() ;
    }
    function db_getAddressData($address)
    {
        return mongo_getAddressData($address) ;
    }
    function db_getAddressDataMany($addresses)
    {
        return mongo_getAddressDataMany($addresses) ;
    }
    function db_getAssetCount($filter = [])  // SIP-042 added filter
    {
        global $settings;
        return mongo_getAssetCount($filter) ;
    }
    function db_getAssetTypeCount() 
    {
        return mongo_getAssetTypeCount() ;
    }
    function db_getAssetData($name)
    {
        return mongo_getAssetData($name) ;
    }
    function db_getAssets($filter)
    {
        return mongo_getAssets($filter) ;
    }
    function db_getAssetByKey($key)
    {
        return mongo_getAssetByKey($key);
    }
    function db_getAssetByName($name, $strict = true)
    {
        return mongo_getAssetByName($name, $strict) ;
    }
    function db_getAssetHoldersByKey($key, $limit = 100, $skip = 0)
    {
        return mongo_getAssetHoldersByKey($key, $limit, $skip) ;
    }
    function db_getAssetMovementsByName($name, $limit = 100, $skip = 0, $strict = true, $filter = null)
    {
        return mongo_getAssetMovementsByName($name, $limit, $skip, $strict, $filter) ;
    }
    function db_getAssetMovesDataWithFilter($filter = array(), $limit = 100, $skip = 0)
    {
        global $settings;
        return mongo_getAssetMovesDataWithFilter($filter, $limit, $skip);
    }
    function db_getAssetMovesCountByName($name)
    {
        return mongo_getAssetMovesCountByName($name) ;
    }

    function db_getAssetDistributionRanking($from_time, $to_time, $limit = 100, $skip = 0)
    {
        return mongo_getAssetDistributionRanking($from_time, $to_time, $limit, $skip) ;
    }

    function db_getAssetConsolidationRanking($from_time, $to_time, $limit = 100, $skip = 0)
    {
        return mongo_getAssetConsolidationRanking($from_time, $to_time, $limit, $skip) ;
    }

    function db_getAssetHoldersCountByKey($key)
    {
        return mongo_getAssetHoldersCountByKey($key) ;
    }
    function db_getAddressesByGroupId($group_id, $limit = 0, $skip = 0, $filter = array(), $projection = array())
    {
        global $settings;
        return mongo_getAddressesByGroupId($group_id, $limit, $skip, $filter, $projection) ;
    }
    function db_getAddressesCountByGroupId($group_id) // from ADDRESSES
    {
        global $settings;
        return mongo_getAddressesCountByGroupId($group_id) ;
    }
    function db_getAddressesCountByBlockIx($block_ix)
    {
        return mongo_getAddressesCountByBlockIx($block_ix) ;
    }
    function db_getAddressesByLastBlockData($block_ix)
    {
        return mongo_getAddressesByLastBlockData($block_ix) ;
    }    
    function db_getAddresesWithTopBalance($limit = 25, $skip = 0, $withburnaddresses = false, $strictlyabovezero = false)
    {
        return mongo_getAddressesWithTopBalance($limit, $skip, $withburnaddresses, $strictlyabovezero) ;
    }
    function db_getAddressMdaoBalanceData($address)
    {
        return mongo_getAddressMdaoBalanceData($address) ;
    }

    function db_getTopAddressesCache(){
        return mongo_getTopAddressesCache() ;
    }
  // >>> AddressGroup Queries <<<    
    function db_getAddressGroupAggregates($group_id)
    {
        return mongo_getAddressGroupAggregates($group_id) ;
    }
    function db_getTopAddressGroups($limit = 1, $skip = 0, $strictlyabovezero = false)
    {
        return mongo_getTopAddressGroups($limit, $skip, $strictlyabovezero) ;
    }
    function db_getTopAddressGroupsByBalance($limit = 1, $skip = 0, $withburnaddresses = false, $strictlyabovezero = false){ // from ADDRESSGROUPS
        return mongo_getTopAddressGroupsByBalance($limit, $skip, $withburnaddresses,$strictlyabovezero) ;
    }
    function db_getAddressGroupById($group_id) // from ADDRESSGROUPS
    {
        global $settings;
        return mongo_getAddressGroupById($group_id) ;
    }
    function db_getAddressGroupMany($group_ids) // from ADDRESSGROUPS
    {
        global $settings;
        return mongo_getAddressGroupMany($group_ids) ;
    }
    function db_getAddressGroupTags($group_id) // from ADDRESSGROUPS
    {
        global $settings;
        return mongo_getAddressGroupTags($group_id) ;
    }
    function db_getAddressTags($address) // from ADDRESSGROUPS
    {
        global $settings;
        return mongo_getAddressTags($address) ;
    }
    function db_getAddressGroupByAddress($address) // from ADDRESSGROUPS
    {
        global $settings;
        return mongo_getAddressGroupByAddress($address) ;
    }
    function db_getAddressGroupCount() 
    {
        return mongo_getAddressGroupCount() ;
    }

    function db_getTopAddressGroupsCache(){
        return mongo_getTopAddressGroupsCache() ;
    }

  // >>> AddressMove Queries <<<    
    function db_getAddressMovesDataByAddress($address, $limit = 100, $skip = 0)
    {
        return mongo_getAddressMovesDataByAddress($address, $limit, $skip);
    }
    function db_getAddressMovesDataWithFilter($filter = array(), $limit = 100, $skip = 0, $sort = array())
    {
        global $settings;
        return mongo_getAddressMovesDataWithFilter($filter, $limit, $skip, $sort);
    }
    function db_getAddressMovesCountByAddress($address)
    {
        return mongo_getAddressMovesCountByAddress($address) ;
    }
    function db_getAddressMovesCountWithFilter($filter = array())
    {
        global $settings;
        return mongo_getAddressMovesCountWithFilter($filter) ;
    }
    function db_getAddressMovesCountByBlockIx($block_ix)
    {
        return mongo_getAddressMovesCountByBlockIx($block_ix) ;
    }
    function db_getTopAddressMovesByTimeRange($start_time, $end_time, $limit = 10, $sort_asc = false, $gte_start = false, $lte_end = true, $hide_rewards = false, $from_zero = false)
    {
        return mongo_getTopAddressMovesByTimeRange($start_time, $end_time, $limit, $sort_asc, $gte_start, $lte_end, $hide_rewards, $from_zero) ;
    }
    function db_getTopAddressMovesByBlockRange($start_block, $end_block, $limit = 10, $sort_asc = false, $gte_start = false, $lte_end = true, $hide_rewards = false, $from_zero = false)
    {
        return mongo_getTopAddressMovesByBlockRange($start_block, $end_block, $limit, $sort_asc, $gte_start, $lte_end, $hide_rewards, $from_zero) ;
    }
    function db_getAddressMovesHourlyAggregates($end_time, $hours)
    {
        return mongo_getAddressMovesHourlyAggregates($end_time, $hours) ;
    }
    function db_getAddressMovesAggregatesByGranularity($address, $from_time, $to_time, $granularity_sec = 3600, $including_to_time = true)
    {
        return mongo_getAddressMovesAggregatesByGranularity($address, $from_time, $to_time, $granularity_sec, $including_to_time) ;
    }

    function db_getAddressMovesInOutTotalsByTimeRange($address, $start_time, $end_time)
    {
        return mongo_getAddressMovesInOutTotalsByTimeRange($address, $start_time, $end_time);
    }


    // Shadow ledger (Gold Rush) accessors.
    function db_getShadowVoutsByBlock($block_ix)
    {
        return mongo_getShadowVoutsByBlock($block_ix) ;
    }
    function db_getShadowRecentPayouts($limit = 25)
    {
        return mongo_getShadowRecentPayouts($limit) ;
    }
    function db_getShadowTotals()
    {
        return mongo_getShadowTotals() ;
    }
    function db_getShadowTopRecipients($limit = 10)
    {
        return mongo_getShadowTopRecipients($limit) ;
    }

    function db_recordGoldRushCache($data)
    {
        return mongo_recordGoldRushCache($data) ;
    }
    function db_getGoldRushCache()
    {
        return mongo_getGoldRushCache() ;
    }

    function db_getAirdropTotals()
    {
        return mongo_getAirdropTotals() ;
    }
    function db_getUnclaimedAirdrops()
    {
        return mongo_getUnclaimedAirdrops();
    }
    function db_getClaimedAirdrops()
    {
        return mongo_getClaimedAirdrops();
    }

  // >>> Log Queries <<<    
    function db_getProblemLogCountByBlockIx($block_ix)
    {
        return mongo_getProblemLogCountByBlockIx($block_ix) ;
    }
    function db_getLastLoaderStop()
    {
        return mongo_getLastLoaderStop() ;
    }
    function db_getLastBlockLoadTime()
    {
        return mongo_getLastBlockLoadTime() ;
    }
    function db_getLoaderLogCount()
    {
        return mongo_getLoaderLogCount() ;
    }
    function db_getLoaderLogList($limit, $skip)
    {
        return mongo_getLoaderLogList($limit, $skip) ;
    }
    function db_getLoaderLogTimers($oid)
    {
        return mongo_getLoaderLogTimers($oid) ;
    }
    function db_validateRequestId($request_id)
    {
        return mongo_validateRequestId($request_id) ;
    }

    function db_validateAdsRequestId($request_id)
    {
        return mongo_validateAdsRequestId($request_id) ;
    }

    function db_createRequest()
    {
        return mongo_createRequest() ;
    }

  // >>> RollbackData Queries <<<  
    function db_getRollbackDataCount() 
    {
        return mongo_getRollbackDataCount()  ;
    }  
    function db_getRollbackData($block_ix)
    {
        return mongo_getRollbackData($block_ix) ;
    }
    // Number of rollback records in [from, to].
    function db_countRollbackRange($from, $to)
    {
        return mongo_countRollbackRange($from, $to);
    }
    // Earlier auto-rollbacks logged to PROBLEMLOG for this VOUT (loop guard).
    function db_countAutoRollbacksForVout($vout_id)
    {
        return mongo_countAutoRollbacksForVout($vout_id);
    }
  // >>> Block State Queries <<<
    // Block lifecycle tracking (BLOCKSTATE).
    function db_setBlockState($block_ix, $state, $block_hash = null)
    {
        return mongo_setBlockState($block_ix, $state, $block_hash);
    }
    function db_clearBlockState($block_ix)
    {
        return mongo_clearBlockState($block_ix);
    }
    function db_getUnfinishedBlockStates()
    {
        return mongo_getUnfinishedBlockStates();
    }
    function db_transitionBlockState($block_ix, $state)
    {
        return mongo_transitionBlockState($block_ix, $state);
    }
    function db_claimBlockState($block_ix)
    {
        return mongo_claimBlockState($block_ix);
    }
  // >>> Host Metric Queries <<<
    function db_getLastHostMetric(){
        return mongo_getLastHostMetric() ;
    }
    function db_getHostMetricsByTimeRange($start_time,$end_time,$gte_start = false, $lte_end = true, $sort_asc = true){
        return mongo_getHostMetricsByTimeRange($start_time,$end_time,$gte_start, $lte_end, $sort_asc) ;
    }
  // >>> Markets Queries <<<    
    function db_getLastMarketsMetric()
    {
        return mongo_getLastMarketsMetric() ;
    }
  // >>> Masternode Chache Queris <<<
    function db_getMasternodeCache($start_time){
        return mongo_getMasternodeCache($start_time) ;
    }
  // >>> Chain Stats Queries <<<
    function db_getLastChainStats(){
        return mongo_getLastChainStats() ;
    }
    function db_getChainStats($timestamp){
        return mongo_getChainStats($timestamp) ;
    }
    function db_getChainStatsMany($from_time, $to_time){
        return mongo_getChainStatsMany($from_time, $to_time) ;
    }
  // >>> Chain Metrics Queries <<<
    function db_getLastChainMetric(){
        return mongo_getLastChainMetric() ;
    }
    function db_getChainMetricsByTimeRange($start_time,$end_time,$gte_start = false, $lte_end = true, $sort_asc = true){
        return mongo_getChainMetricsByTimeRange($start_time,$end_time,$gte_start, $lte_end, $sort_asc) ;
    }
    function db_getChainMetricsHourlyAggregates($end_time, $hours)
    {
        return mongo_getChainMetricsHourlyAggregates($end_time, $hours) ;
    }
    function db_getChainMetricsAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600)
    {
        return mongo_getChainMetricsAggregatesByGranularity($from_time, $to_time, $granularity_sec) ;
    }
    function db_getMarketMetricsByTimeRange($start_time,$end_time,$gte_start = false, $lte_end = true, $sort_asc = true){
        return mongo_getMarketMetricsByTimeRange($start_time,$end_time,$gte_start, $lte_end, $sort_asc) ;
    } 
    function db_getMarketAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getMarketAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getMiningCache(){
        return mongo_getMiningCache() ;
    }

    function db_getOverviewCache(){
        return mongo_getOverviewCache() ;
    }
  // >>> Block Stats Queries <<<
    function db_getBlockStats($timestamp){
        return mongo_getBlockStats($timestamp) ;
    }
    function db_getLastBlockStats(){
        return mongo_getLastBlockStats() ;
    }
    function db_getBlockStatsRange($start_time, $end_time, $sort_asc = true, $limit = -1){
        return mongo_getBlockStatsRange($start_time, $end_time, $sort_asc, $limit) ;
    }
    function db_getTxTypesAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getTxTypesAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getTxSizesAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getTxSizesAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getAssetTypesAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getAssetTypesAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getVoutTypesAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getVoutTypesAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getBlockVersionsAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getBlockVersionsAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getBlockTypesAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getBlockTypesAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getBlockStatsAggregatesByGranularity($start_time, $end_time, $granularity_sec = 3600){
        return mongo_getBlockStatsAggregatesByGranularity($start_time, $end_time,$granularity_sec) ;
    }
    function db_getBlockRewardAggregatesDaily($from_time, $to_time){
        return mongo_getBlockRewardAggregatesDaily($from_time, $to_time) ;
    }
  // >>> API Rate Limiter Queries <<<
    function db_getApiCallsCountByIpAddress($ip_address) 
    {
        return mongo_getApiCallsCountByIpAddress($ip_address) ;
    }
  // >>> Usage Log Queries <<<
    function db_getPageAggregates($since){
      return mongo_getPageAggregates($since) ;
    }
    function db_getApiAggregates($since){
        global $settings;
        return mongo_getApiAggregates($since) ;
      }
  // >>> Asset Queries <<<
    function db_getAssetMovesCount($filter = [])  // SIP-042
    {
        global $settings;
        return mongo_getAssetMovesCount($filter);
    }
    function db_getLastCreatedAssets($limit = 100, $skip = 0, $filter = null)  // SIP-061 added filter
    {
        global $settings;
        return mongo_getLastCreatedAssets($limit, $skip, $filter);
    }


// --- MongoDB Inserts ---
    function db_recordVoutDataBulk($block_record)
    {
        return mongo_recordVoutDataBulk($block_record) ;
    }
    function db_recordAddressDataBulk($block_record)
    {
        return mongo_recordAddressDataBulk($block_record) ;
    }
    function db_recordAddressMovesDataBulk($block_record)
    {
        return mongo_recordAddressMovesDataBulk($block_record) ;
    }
    function db_recordTxDataBulk($block_record)
    {
        return mongo_recordTxDataBulk($block_record) ;
    }
    function db_recordBlockData($block_record)
    {
        return mongo_recordBlockData($block_record) ;
    }
    function db_recordLogDataBulk($block_record)
    {
        return mongo_recordLogDataBulk($block_record) ;
    }
    function db_recordLogData($log_record)
    {
        return mongo_recordLogData($log_record) ;
    }
    function db_recordTxData($tx_record)
    {
        return mongo_recordTxData($tx_record) ;
    }
    function db_recordMarketDataBulk($market_record)
    {
        return mongo_recordMarketDataBulk($market_record) ;
    }
    function db_recordChainDataBulk($chain_record)
    {
        return mongo_recordChainDataBulk($chain_record) ;
    }
    function db_recordMnCacheDataBulk($mncache_record)
    {
        return mongo_recordMnCacheDataBulk($mncache_record) ;
    }
    function db_recordHostDataBulk($host_record)
    {
        return mongo_recordHostDataBulk($host_record) ;
    }
    function db_recordBlockStats($block_record){
        return mongo_recordBlockStats($block_record) ;
    }
    function db_recordApiCall($ip_address)
    {
        return mongo_recordApiCall($ip_address) ;
    }
    function db_recordApiLog($ip_address)
    {
        return mongo_recordApiLog($ip_address) ;
    }

    function db_recordTopAddressesCache($data){
        return mongo_recordTopAddressesCache($data) ;
    }
    function db_recordMiningCache($data){
        return mongo_recordMiningCache($data) ;
    }

    function db_recordOverviewCache($data){
        return mongo_recordOverviewCache($data) ;
    }

    function db_recordTopAddressGroupsCache($data){
        return mongo_recordTopAddressGroupsCache($data) ;
    }

    function db_recordPageLog($event)
    {
        return mongo_recordPageLog($event) ;
    }
    function db_recordPageMetrics($metrics)
    {
        return mongo_recordPageMetrics($metrics) ;
    }

    function db_recordAdsMetrics($metrics)
    {
        return mongo_recordAdsMetrics($metrics) ;
    }

    function db_recordLoaderLog($loaderLog)
    {
        return mongo_recordLoaderLog($loaderLog) ;
    }
    // V2 Methods
        function db_insertBlockRollbacks($block){
            global $settings;
            return mongo_insertBlockRollbacks($block) ;
        }
        function db_insertVouts($block){
            global $settings;
            return mongo_insertVouts($block) ;
        }
        function db_insertAirdrops($block){
            global $settings;
            return mongo_insertAirdrops($block) ;
        }
        function db_insertTxs($block){
            global $settings;
            return mongo_insertTxs($block) ;
        }
        function db_insertAddressMoves($block){
            global $settings;
            return mongo_insertAddressMoves($block) ;
        }
        function db_insertAssetMoves($block){
            global $settings;
            return mongo_insertAssetMoves($block) ;
        }
        function db_insertBlock($block){
            global $settings;
            return mongo_insertBlock($block) ;
        }

        function db_insertMdaoBalances($mdao_balances){
            global $settings;
            return mongo_insertMdaoBalances($mdao_balances) ;
        }


// --- MongoDb Updates ---
    function db_updateAddressGroups($block_record)
    {
        global $settings;
        return mongo_updateAddressGroups($block_record) ;
    }
    function db_updateAddressGroups_bak($block_record)
    {
        return mongo_updateAddressGroups_bak($block_record) ;
    }
    function db_setVoutSpent($vout_id, $vin_id)
    {
        return mongo_setVoutSpent($vout_id, $vin_id) ;
    }
    function db_setVoutUnspent($vout_id)
    {
        return mongo_setVoutUnspent($vout_id) ;
    }

    // V2 Methods
        function db_updateVoutsSpent($block){
            global $settings;
            return mongo_updateVoutsSpent($block) ;
        }
        function db_updateAirdropsClaimed($block){
            global $settings;
            return mongo_updateAirdropsClaimed($block) ;
        }
        function db_updateAddresses($block){
            global $settings;
            return mongo_updateAddresses($block) ;
        }
        function db_updateAssets($block){
            global $settings;
            return mongo_updateAssets($block) ;
        }
        function db_updateGroups($block){
            global $settings;
            return mongo_updateGroups($block) ;
        }
        function db_updateBlockStats($block){
            global $settings;
            return mongo_updateBlockStats($block) ;
        }
        function db_updateChainStats($chain_stats){
            global $settings;
            return mongo_updateChainStats($chain_stats) ;
        }
// --- MongoDB Deletes ---

    function db_deleteOldRollbackRecords($block_ix){
        global $settings;
        return mongo_deleteOldRollbackRecords($block_ix) ;
    }
    function db_deleteAddressMovesByBlockHeight($block_ix){
        return mongo_deleteAddressMovesByBlockHeight($block_ix) ;
        
    }
    function db_deleteVoutsByBlockHeight($block_ix){
        return mongo_deleteVoutsByBlockHeight($block_ix) ;
        
    }
    function db_rollbackBlockStatsV1($block){
        return mongo_rollbackBlockStatsV1($block) ;
    }
    function db_rollbackAddressBalances($block){
        return mongo_rollbackAddressBalances($block) ;
    }
    function db_rollbackTxsV1($block){
        return mongo_rollbackTxsV1($block) ;
    }
    function db_rollbackTxs($block_ix){
        return mongo_rollbackTxs($block_ix) ;
    }
    // V2 Methods
        function db_deleteBlock($block_ix){
            global $settings;
            return mongo_deleteBlock($block_ix) ;
        }
        function db_rollbackBlockStats($rollback_record){
            global $settings;
            return mongo_rollbackBlockStats($rollback_record) ;
        }
        function db_rollbackAddressGroups($rollback_record){
            global $settings;
            return mongo_rollbackAddressGroups($rollback_record) ;
        }
        function db_rollbackAddresses($rollback_record){
            global $settings;
            return mongo_rollbackAddresses($rollback_record) ;
        }
        function db_rollbackAssets($rollback_record){
            global $settings;
            return mongo_rollbackAssets($rollback_record) ;
        }
        function db_rollbackAssetMoves($block_ix){
            global $settings;
            return mongo_rollbackAssetMoves($block_ix) ;
        }
        function db_rollbackAddressMoves($block_ix){
            global $settings;
            return mongo_rollbackAddressMoves($block_ix) ;
        }
        function db_rollbackVouts($block_ix){
            global $settings;
            return mongo_rollbackVouts($block_ix) ;
        }
        function db_rollbackVoutSpent($rollback_record){
            global $settings;
            return mongo_rollbackVoutSpent($rollback_record) ;
        }
        function db_rollbackAirdrops($block_ix){
            global $settings;
            return mongo_rollbackAirdrops($block_ix) ;
        }
        function db_rollbackAirdropsClaimed($rollback_record){
            global $settings;
            return mongo_rollbackAirdropsClaimed($rollback_record) ;
        }
        function db_deleteRollbackRecord($block_ix){
            global $settings;
            return mongo_deleteRollbackRecord($block_ix) ;
        }
        function db_deleteChainStats($timestamp){
            global $settings;
            return mongo_deleteChainStats($timestamp) ;
        }
// --- SECURITY QUERIES ---
        function db_createUserAccount(string $name, string $password, array $groups = []){
            global $settings;
            return mongo_createUserAccount($name, $password, $groups) ;
        }
        function db_setUserPassword(string $name, string $pwd_hash){
            return mongo_setUserPassword($name, $pwd_hash) ;
        }
        function db_getUserAccountByName(string $name){
            global $settings;
            return mongo_getUserAccountByName($name) ;
        }
        function db_recordSession($user){
            global $settings;
            return mongo_recordSession($user) ;
        }
        function db_deleteUserSessions(array $filter){
    return mongo_deleteUserSessions($filter);
}
function db_recordLoginFailure(string $ip, string $user){
    return mongo_recordLoginFailure($ip, $user);
}
function db_countLoginFailures(string $field, string $value, int $window_sec){
    return mongo_countLoginFailures($field, $value, $window_sec);
}
function db_getUserSession($session){
            global $settings;
            return mongo_getUserSession($session) ;
        }
// ---
    function rpp($value){
        global $settings;
        if (array_key_exists("processing_precision",$settings)){
            try {
                $pp = (int)$settings["processing_precision"];
            } catch (Exception $ex) { $pp = 8; }
        } else {
            $pp = 8;
        }
        return round($value, $pp);
    }
    function fpp($value, $decimals = null){
        global $settings;
        is_null($decimals) ? $dp = (int)$settings["processing_precision"] : $dp = $decimals;
        return splitFloat(dflt($value), $dp, $dp)->string;
    }
    function cdec(mixed $value, $decimals = null) : ?Decimal {
        global $settings;
        if (is_null($value)){ return null; }
        isset($settings['arbitrary_precision']) ? $ap = (int)$settings['arbitrary_precision'] : $ap = 64;
        is_null($decimals) ?  $dp = (int)$settings["processing_precision"] : $dp = (int)$decimals ;
        if (gettype($value) != "object" && is_numeric($value)){
            $vs = (string)$value;
            // more significant digits than arbitrary_precision: rounding is intended
            $dv = @new Decimal($vs, $ap);
            $rv = $dv->round($dp);
            return $rv;
        } else {
            if (get_class($value) == "Decimal\Decimal"){
                $rv = $value->round($dp);
                return $rv;
            } else if (get_class($value) == "MongoDB\BSON\Decimal128") {
                $rv = new Decimal((string)$value);
                return $rv;
            } else {
                throw new Exception("\nUnknown data type, cdec processing not possible.\n", 7001);
                die();
            }
        }
    }
    function cmdec($value, $decimals = null){
        // convert to MongoDB\BSON\Decimal128
        global $settings;
        if (is_null($value)){ return null; }
        is_null($decimals) ?  $dp = (int)$settings["processing_precision"] : $dp = (int)$decimals ;
        if (gettype($value) != "object" && is_numeric($value)){
            $vs = number_format($value,$dp,'.','');
            $rv = new MongoDB\BSON\Decimal128($vs);
            //$rv = $dv->round($dv);
            return $rv;
        } else {
            if (get_class($value) == "Decimal\Decimal"){
                $rv = new MongoDB\BSON\Decimal128((string)$value->round($dp));
                return $rv;
            } else if (get_class($value) == "MongoDB\BSON\Decimal128") {
                $rv = new MongoDB\BSON\Decimal128((string) $value);
                return $rv;
            } else {
                throw new Exception("\nUnknown data type, cmdec processing not possible.\n", 7002);
                die();
            }
        }
    }
    function cbool($value){
        if (is_numeric($value)){
            $v = (int)$value;
            $x = ($v > 0) ?  true :  false;
            return $x; 
        }
        return null;
    }
    function dflt($value, $decimals = null){
        global $settings;
        if (is_null($value)) { return null; }
        if (!is_null($decimals) && is_int($decimals)) { $dp = $decimals;} else { $dp = (int)$settings["processing_precision"];} 
        if (is_numeric($value)){
            $vr = (float) $value;
            $res = round($vr,$dp);
            return $res;
        }
        if (gettype($value) != "object"){
            throw new Exception("\nData type must be Decimal\Decimal or MongoDB\BSON\Decimal128, dflt processing not possible.\n", 7003);
            die();
        } else {
            
            if (get_class($value) == "Decimal\Decimal"){
                $vr = $value->round($dp);
                $res = $vr->toFloat();
                return $res;
            } else if (get_class($value) == "MongoDB\BSON\Decimal128"){ 
                $vr = (float)((string) $value);
                $res = round($vr,$dp);
                return $res;
            } else {
                throw new Exception("\nData type must be Decimal\Decimal or MongoDB\BSON\Decimal128, dflt processing not possible.\n", 7003);
                die();
            }
        }

    }
    function generateUuid( $prefix='' )
    {
        $data = random_bytes(16);
    
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        if ($prefix != ""){
            return vsprintf($prefix.'-%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } else {
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            //bb4f6e22-52d1-420b-9e45-de54e444e6bf
        }
    }
    function generateHash( $data, $prefix='' ){
        //if (!empty($prefix)) { $prefix=$prefix.'-'; }
        $res = hash('ripemd160', $data, false);
        return $prefix.$res;
    }

////   INIT PROCEDURE ////
    global $settings;
    global $dbm;
    $dbm = db_initConnection();
?>
