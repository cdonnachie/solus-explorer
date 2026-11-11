<?php
    require __DIR__ . '/../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';

    header('Content-Type: application/json');
    $cip = ui_GetUserIpAddress();
    $session = sec_Authenticate($cip, ui_GetUserAgent());
    if (!$session->authenticated || !sec_sameOrigin() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(401);
        echo '{"status":"error","error":"Unauthorized"}';
        exit;
    }
    $apt = new TimeTracker;
    $apt->createTimer("total", 0);

    const LBL_MAX_TAGS = 10;
    const LBL_LIST_LIMIT = 1000;

    // raw POST value: labels are validated, not HTML-encoded, before they reach the database
    function lbl_post(string $name) : string {
        $v = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW);
        return is_string($v) ? $v : '';
    }
    function lbl_error(string $msg) : array {
        return ['status' => 'error', 'error' => $msg];
    }
    function lbl_amount($value) : string {
        global $settings;
        $p = (int)($settings['overview_precision'] ?? 2);
        $s = splitFloat($value ?? 0, $p, $p);
        if (!is_numeric($s->whole)) { return (string)$s->string; }
        return number_format((float)$s->whole, 0, '.', ',').($p > 0 ? '.'.$s->fraction : '');
    }
    function lbl_tags($doc) : array {
        return array_values(array_filter((array)($doc->tags ?? []), 'is_string'));
    }
    function lbl_group(string $group_id) : ?array {
        $g = db_getLabelGroup($group_id);
        if (!$g) { return null; }
        return ['id' => (string)$g->_id, 'tags' => lbl_tags($g), 'count' => (int)($g->address_count ?? 0), 'balance' => lbl_amount($g->balance ?? 0)];
    }
    function lbl_address(string $address) : ?array {
        $a = db_getLabelAddress($address);
        if (!$a) { return null; }
        return [
            'id'      => (string)$a->_id,
            'alias'   => (isset($a->alias) && is_string($a->alias)) ? $a->alias : '',
            'balance' => lbl_amount($a->balance ?? 0),
            'group'   => !empty($a->group_id) ? lbl_group((string)$a->group_id) : null,
        ];
    }
    function lbl_validId(string $id) : bool {
        return $id !== '' && strlen($id) <= 128 && !preg_match('/[\x00-\x1f\x7f]/', $id);
    }

    $action = lbl_post('action');
    try {
        switch ($action) {
            case 'lookup':
                $id = trim(lbl_post('id'));
                if (!lbl_validId($id)) { $res = lbl_error('Enter an address or a group id.'); break; }
                if (strpos($id, 'csag-') === 0) {
                    $g = lbl_group($id);
                    $res = $g ? ['status' => 'ok', 'kind' => 'group', 'group' => $g] : lbl_error('No address group with this id.');
                } else {
                    $a = lbl_address($id);
                    $res = $a ? ['status' => 'ok', 'kind' => 'address', 'address' => $a] : lbl_error('This address has no transactions in the database.');
                }
                break;

            case 'alias':
                $id = lbl_post('id');
                $alias = lbl_post('alias');
                if (!lbl_validId($id) || !db_getLabelAddress($id)) { $res = lbl_error('This address is not in the database.'); break; }
                if ($alias !== '' && ($e = ui_labelError($alias)) !== '') { $res = lbl_error($e); break; }
                db_setAddressAlias($id, $alias);
                $res = ['status' => 'ok', 'kind' => 'address', 'address' => lbl_address($id), 'message' => $alias === '' ? 'Alias removed' : 'Alias saved'];
                break;

            case 'tags':
                $id = lbl_post('id');
                $raw = json_decode(lbl_post('tags'), true);
                if (!lbl_validId($id) || !db_getLabelGroup($id)) { $res = lbl_error('This address group is not in the database.'); break; }
                if (!is_array($raw)) { $res = lbl_error('The tag list could not be read. Reload the page and try again.'); break; }
                $tags = []; $seen = [];
                foreach ($raw as $t) {
                    if (!is_string($t) || ($e = ui_labelError($t)) !== '') { $res = lbl_error(is_string($t) ? "Tag “".$t."”: ".$e : 'The tag list could not be read.'); break 2; }
                    $k = ui_labelKey($t);
                    if (isset($seen[$k])) { continue; }
                    $seen[$k] = true;
                    $tags[] = $t;
                }
                if (count($tags) > LBL_MAX_TAGS) { $res = lbl_error('A group can have at most '.LBL_MAX_TAGS.' tags.'); break; }
                db_setGroupTags($id, $tags);
                $res = ['status' => 'ok', 'kind' => 'group', 'group' => lbl_group($id), 'message' => count($tags) ? 'Tags saved' : 'Tags removed'];
                break;

            case 'list':
                $ix = db_getLabelIndexes();
                $res = ['status' => 'ok', 'indexes' => $ix, 'limit' => LBL_LIST_LIMIT, 'aliases' => null, 'tags' => null];
                if ($ix['alias']) {
                    $res['aliases'] = array_map(function ($a) {
                        return ['alias' => (string)$a->alias, 'id' => (string)$a->_id, 'balance' => lbl_amount($a->balance ?? 0)];
                    }, db_getLabelAliases(LBL_LIST_LIMIT));
                }
                if ($ix['tags']) {
                    $rows = [];
                    foreach (db_getLabelGroups(LBL_LIST_LIMIT) as $g) {
                        foreach (lbl_tags($g) as $t) {
                            $rows[] = ['tag' => $t, 'id' => (string)$g->_id, 'count' => (int)($g->address_count ?? 0), 'balance' => lbl_amount($g->balance ?? 0)];
                        }
                    }
                    usort($rows, function ($x, $y) { return strcasecmp($x['tag'], $y['tag']) ?: $y['count'] <=> $x['count']; });
                    $res['tags'] = $rows;
                }
                break;

            default:
                $res = lbl_error('Unknown request.');
        }
    } catch (Throwable $e) {
        $res = lbl_error('Database error: '.$e->getMessage());
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    $apt->stopTimer("total");
    ui_LogPageEvent($cip, "admin/labels", (int)$apt->getDurationMicroSeconds("total"), ["action" => $action]);
