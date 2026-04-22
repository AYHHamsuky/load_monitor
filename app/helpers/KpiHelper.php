<?php
class KpiHelper {

    public static function supplyHours($rows) {
        $hours = [];
        foreach ($rows as $r) {
            if ($r['load_reading'] !== null) {
                $hours[$r['fdr11kv_code']][] = $r['periods'];
            }
        }
        return array_map('count', $hours);
    }
}
