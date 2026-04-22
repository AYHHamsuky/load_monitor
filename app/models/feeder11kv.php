<?php
class Feeder11kv {

    public static function by33kv($code) {
        $db = DB::connect();
        $stmt = $db->prepare("
            SELECT 11kv_code, 11kv_fdr_name, band
            FROM fdr11kv
            WHERE 33kv_code = ?
            ORDER BY 11kv_fdr_name
        ");
        $stmt->execute([$code]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
