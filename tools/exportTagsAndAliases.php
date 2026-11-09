<?php 
require "../settings.php";
global $settings;
require "../lib/explorerDb.php";
//global $dbm;
$filter = ['tags' => ['$exists' => true]];
$options = [];
$query = new MongoDB\Driver\Query(
   $filter, 
   $options
);

$groups = mongo_execDbQuery($query, "ADDRESSGROUPS");
foreach ($groups as $group){
    if (isset($group->tags) && count($group->tags)>0){
        echo 'db.ADDRESSGROUPS.updateOne({_id: "'.$group->_id.'"},{$set:{"tags":'.json_encode($group->tags).'}});'.PHP_EOL;
    }
}

$filter = ['alias' => ['$exists' => true]];
$options = [];
$query = new MongoDB\Driver\Query(
   $filter, 
   $options
);
$addresses = mongo_execDbQuery($query, "ADDRESSES");
foreach ($addresses as $address){
    if (isset($address->alias) && !empty($address->alias)){
        echo 'db.ADDRESSES.updateOne({_id: "'.$address->_id.'"},{$set:{"alias":'.json_encode($address->alias).'}});'.PHP_EOL;
    }
}
?>