<?php
// public/ajax/get_existing_data.php
// Fetch existing data for correction form auto-populate

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!Auth::check()) {
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated'
        ]);
        exit;
    }

    $user = Auth::user();
    $db = Database::getInstance();

    // Get parameters
    $feeder_code = trim($_POST['feeder_code'] ?? '');
    $entry_date = trim($_POST['entry_date'] ?? '');
    $entry_hour = (int)($_POST['entry_hour'] ?? 0);
    $data_table = trim($_POST['data_table'] ?? '');

    // Validation
    if (empty($feeder_code) || empty($entry_date) || $entry_hour < 1 || $entry_hour > 24) {
        throw new Exception('Invalid parameters');
    }

    // Validate table name (security)
    if (!in_array($data_table, ['fdr11kv_data', 'fdr33kv_data'])) {
        throw new Exception('Invalid data table');
    }

    // Fetch existing data
    $stmt = $db->prepare("
        SELECT 
            load_reading,
            fault_code,
            fault_remark,
            entered_by,
            created_at
        FROM {$data_table}
        WHERE feeder_code = ?
        AND entry_date = ?
        AND entry_hour = ?
    ");

    $stmt->execute([$feeder_code, $entry_date, $entry_hour]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => [
                'load_reading' => $data['load_reading'],
                'fault_code' => $data['fault_code'] ?? '',
                'fault_remark' => $data['fault_remark'] ?? '',
                'entered_by' => $data['entered_by'],
                'created_at' => $data['created_at']
            ],
            'message' => 'Data found'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No data found for this feeder/date/hour combination',
            'data' => null
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ]);
}