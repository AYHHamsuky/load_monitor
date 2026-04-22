<?php
class LoadReading {

    public static function save11kv($data) {
        $db = DB::connect();

        $stmt = $db->prepare("
            INSERT INTO fdr11kv_data
            (date, fdr11kv_code, day_hour, load_reading, fault_code, fault_remark, user_id)
            VALUES (CURDATE(), ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['feeder'],
            $data['hour'],
            $data['load'],
            $data['fault'],
            $data['remark'],
            $data['user']
        ]);
    }

    public static function todayBy33kv($code) {
        $db = DB::connect();

        $stmt = $db->prepare("
            SELECT *
            FROM fdr11kv_data d
            JOIN fdr11kv f ON f.11kv_code = d.fdr11kv_code
            WHERE f.33kv_code = ?
              AND d.date = CURDATE()
        ");
        $stmt->execute([$code]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
