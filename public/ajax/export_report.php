<?php
/**
 * Report Export Handler
 * Path: /public/ajax/export_report.php
 * 
 * Handles exporting reports to CSV, Excel, and PDF formats
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Auth.php';

try {
    // Verify user is authenticated
    if (!Auth::check()) {
        die('Unauthorized access');
    }

    $user = Auth::user();

    // Get parameters
    $export_format = $_POST['export_format'] ?? 'csv';
    $report_type = $_POST['report_type'] ?? 'daily';
    $date_from = $_POST['date_from'] ?? date('Y-m-d');
    $date_to = $_POST['date_to'] ?? date('Y-m-d');
    $feeder_filter = $_POST['feeder_filter'] ?? 'ALL';
    $report_data = isset($_POST['report_data']) ? json_decode($_POST['report_data'], true) : [];

    if (empty($report_data)) {
        die('No data to export');
    }

    // Generate filename
    $filename = generateFilename($report_type, $date_from, $date_to);

    // Export based on format
    switch ($export_format) {
        case 'csv':
            exportCSV($report_data, $filename, $report_type);
            break;

        case 'excel':
            exportExcel($report_data, $filename, $report_type);
            break;

        case 'pdf':
            exportPDF($report_data, $filename, $report_type, $date_from, $date_to, $feeder_filter, $user);
            break;

        default:
            die('Invalid export format');
    }

    // Log export
    try {
        $db = Database::connect();
        $log_stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, created_at)
            VALUES (?, 'export_report', ?, NOW())
        ");
        $log_description = "Exported {$report_type} report as {$export_format}";
        $log_stmt->execute([$user['id'], $log_description]);
    } catch (Exception $e) {
        error_log('Failed to log export: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Export Error: ' . $e->getMessage());
    die('Export failed: ' . $e->getMessage());
}

/**
 * Generate filename for export
 */
function generateFilename($report_type, $date_from, $date_to) {
    $date_range = $date_from === $date_to ? $date_from : "{$date_from}_to_{$date_to}";
    return strtoupper($report_type) . "_Report_{$date_range}";
}

/**
 * Export to CSV
 */
function exportCSV($data, $filename, $report_type) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if (!empty($data)) {
        // Get headers from first row keys
        $headers = array_keys($data[0]);
        
        // Beautify headers
        $headers = array_map(function($header) {
            return ucwords(str_replace('_', ' ', $header));
        }, $headers);

        // Write headers
        fputcsv($output, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
    }

    fclose($output);
    exit;
}

/**
 * Export to Excel (HTML table format - opens in Excel)
 */
function exportExcel($data, $filename, $report_type) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
    echo '<xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>' . htmlspecialchars($report_type) . ' Report</x:Name>';
    echo '<x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml>';
    echo '</head>';
    echo '<body>';

    if (!empty($data)) {
        echo '<table border="1" style="border-collapse: collapse;">';
        
        // Headers
        echo '<thead style="background-color: #4f46e5; color: white; font-weight: bold;">';
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            $beautified_header = ucwords(str_replace('_', ' ', $header));
            echo '<th style="padding: 10px;">' . htmlspecialchars($beautified_header) . '</th>';
        }
        echo '</tr>';
        echo '</thead>';

        // Data
        echo '<tbody>';
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td style="padding: 8px;">' . htmlspecialchars($cell ?? '') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
    }

    echo '</body>';
    echo '</html>';
    exit;
}

/**
 * Export to PDF (Basic HTML to PDF)
 */
function exportPDF($data, $filename, $report_type, $date_from, $date_to, $feeder_filter, $user) {
    // For a basic PDF, we'll output HTML with print-friendly CSS
    // In production, you might want to use a library like TCPDF or mPDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($filename) ?></title>
        <style>
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 1cm;
                }
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #4f46e5;
                padding-bottom: 15px;
            }
            .header h1 {
                margin: 0;
                color: #4f46e5;
                font-size: 24px;
            }
            .header p {
                margin: 5px 0;
                color: #64748b;
            }
            .info-section {
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            .info-item {
                font-size: 11px;
            }
            .info-item strong {
                color: #1e293b;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 11px;
            }
            thead {
                background: #4f46e5;
                color: white;
            }
            th, td {
                padding: 10px;
                text-align: left;
                border: 1px solid #e2e8f0;
            }
            tbody tr:nth-child(even) {
                background: #f8fafc;
            }
            tbody tr:hover {
                background: #eef2ff;
            }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 2px solid #e2e8f0;
                text-align: center;
                color: #64748b;
                font-size: 10px;
            }
            .print-button {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                background: #4f46e5;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
            }
            .print-button:hover {
                background: #4338ca;
            }
            @media print {
                .print-button {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <button class="print-button" onclick="window.print()">🖨️ Print / Save as PDF</button>
        
        <div class="header">
            <h1><?= strtoupper(htmlspecialchars($report_type)) ?> REPORT</h1>
            <p>Load Monitoring System - Comprehensive Report</p>
        </div>

        <div class="info-section">
            <div class="info-item">
                <strong>Report Period:</strong><br>
                <?= htmlspecialchars($date_from) ?> to <?= htmlspecialchars($date_to) ?>
            </div>
            <div class="info-item">
                <strong>Feeder Selection:</strong><br>
                <?= htmlspecialchars($feeder_filter === 'ALL' ? 'All Feeders' : $feeder_filter) ?>
            </div>
            <div class="info-item">
                <strong>Generated By:</strong><br>
                <?= htmlspecialchars($user['staff_name']) ?> (<?= htmlspecialchars($user['payroll_id']) ?>)
            </div>
            <div class="info-item">
                <strong>Generated On:</strong><br>
                <?= date('F j, Y g:i A') ?>
            </div>
            <div class="info-item">
                <strong>Total Records:</strong><br>
                <?= number_format(count($data)) ?>
            </div>
            <div class="info-item">
                <strong>Report Type:</strong><br>
                <?= ucwords(htmlspecialchars($report_type)) ?>
            </div>
        </div>

        <?php if (!empty($data)): ?>
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($data[0]) as $header): ?>
                        <th><?= ucwords(str_replace('_', ' ', htmlspecialchars($header))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell ?? '-') ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; padding: 40px; color: #64748b;">
            No data available for the selected criteria.
        </p>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Load Monitoring System</strong> | Generated on <?= date('F j, Y') ?> at <?= date('g:i A') ?></p>
            <p>This is a computer-generated report. No signature is required.</p>
        </div>

        <script>
            // Auto-print on load for PDF export
            window.addEventListener('load', function() {
                // Uncomment the line below to auto-print
                // window.print();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>
