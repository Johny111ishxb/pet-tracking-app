<?php
require_once __DIR__ . '/../phpqrcode/qrlib.php'; 

function ensure_qr_png($token, $qr_url, $size = 8)
{
    $qrDir = __DIR__ . '/../qr_codes/';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0777, true);
    }

    $localFile = $qrDir . $token . '.png';
    $webPath   = '../qr_codes/' . $token . '.png';
    $saved     = false;

    try {
        
        $safeSize = min(max((int)$size, 1), 10);
        @QRcode::png($qr_url, $localFile, QR_ECLEVEL_L, $safeSize, 2);
        if (file_exists($localFile) && filesize($localFile) > 0) {
            $saved = true;
        }
    } catch (Throwable $e) {
        error_log("Local QR generation failed: " . $e->getMessage());
    }

   //from googlechart to quickchart
    if (!$saved) {
        try {
            $quickchartUrl = "https://quickchart.io/qr?text=" . urlencode($qr_url) . "&size=" . ($size * 30);
            $qrData = @file_get_contents($quickchartUrl);

            if ($qrData !== false) {
                file_put_contents($localFile, $qrData);
                $saved = true;
            } else {
                error_log("QuickChart fallback failed to retrieve QR for token: $token");
            }
        } catch (Throwable $e) {
            error_log("QuickChart fallback error: " . $e->getMessage());
        }
    }

    return [
        'local_file' => $localFile,
        'web_path'   => $webPath,
        'saved'      => $saved,
        'source'     => $saved ? (file_exists($localFile) ? 'local or fallback' : 'none') : 'failed',
    ];
}
?>