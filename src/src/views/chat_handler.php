<?php
// chat_handler.php - Updated Endpoint to v1
define('API_REQUEST', true);
ob_start();

header('Content-Type: application/json');
session_start();

$response = [];

try {
    require_once __DIR__ . '/../../config/db.php';
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sesi habis. Silakan login kembali.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT message, response FROM chat_logs WHERE user_id = ? ORDER BY id DESC LIMIT 20");
        $stmt->execute([$_SESSION['user_id']]);
        $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        $response = ['success' => true, 'history' => $history];
    } 
    else {
        $userMessage = trim($_POST['message'] ?? '');
        if (empty($userMessage)) throw new Exception('Pesan kosong.');

        $apiKeys = [
            "AIzaSyCz12VYta2eeotX5tCVcBTuf5q8wnm2znA",
            "AIzaSyA7nI9mvuRoHFmDIWZOCKsnLB51EUvimV4",
            "AIzaSyAv-GmEdb3yAJPDHs0xAniD3WRIvfBf7HU",
            "AIzaSyAVajXyJTdzBPmSi_JLfjdJCcZBC8ZwEqY",
            "AIzaSyCI-HTcL7EzJYeO-pBnclp9kSCpLQzzuho",
            "AIzaSyAgPKq2Roe9FXvVh5c3rxUBys4kJBjOlX4",
            "AIzaSyC7jNKGfRv3uE3VSHFgeRU5zaiRnoC_BEI",
            "AIzaSyDvLIgfdnpbnL7biDayxCV-ywBLrolF3F4",
            "AIzaSyAiHmF0AL35HzqR_mTVCybvr-3wiPMm23c"
        ];
        
        // shuffle($apiKeys); // Priority-based looping is better if we have many keys
        $aiReply = "";
        $success = false;
        $errorLogs = [];

        $models = [
            ["v1beta", "gemini-1.5-flash-latest"],
            ["v1beta", "gemini-1.5-flash"],
            ["v1", "gemini-1.5-flash"],
            ["v1beta", "gemini-pro"],
            ["v1", "gemini-pro"],
            ["v1beta", "gemini-2.0-flash-exp"]
        ];

        foreach ($apiKeys as $apiKey) {
            foreach ($models as $mInfo) {
                $apiVer = $mInfo[0];
                $modelName = $mInfo[1];
                
                $apiUrl = "https://generativelanguage.googleapis.com/{$apiVer}/models/{$modelName}:generateContent?key=" . $apiKey;
                $payload = [
                    "contents" => [["parts" => [["text" => "Nama kamu adalah StepUp AI. Kamu adalah asisten belajar yang cerdas dan ramah. Pakai Bahasa Indonesia. Bantu siswa mengerti konsep dengan penjelasan yang mudah. Pertanyaan: " . $userMessage]]]],
                    "generationConfig" => ["temperature" => 0.7, "maxOutputTokens" => 800]
                ];

                $ch = curl_init($apiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 10,
                ]);
                    
                $apiResult = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $resData = json_decode($apiResult, true);
                    $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    
                    if (!empty(trim($rawText))) {
                        $aiReply = $rawText;
                        $success = true;
                        break 2; // Success! Exit both loops
                    } else {
                        $errorLogs[] = "{$modelName}:EmptyResponse";
                    }
                } else {
                    $errorLogs[] = "{$modelName}-{$apiVer}({$httpCode})";
                }
            }
        }

        if (!$success) {
            $aiReply = "Mohon maaf, layanan AI sedang mengalami gangguan teknis (Limit/API Error). Silakan coba lagi beberapa saat lagi atau hubungi administrator.";
        }

        // SIMPAN KE DATABASE
        try {
            $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, message, response) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $userMessage, $aiReply]);
        } catch (Exception $e) {}

        $response = ['success' => true, 'reply' => $aiReply];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'reply' => 'Eror: ' . $e->getMessage()];
}

while (ob_get_level()) ob_end_clean();
echo "<!--JSON_START-->" . json_encode($response) . "<!--JSON_END-->";
exit;
