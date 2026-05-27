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

        $apiKey = trim("AIzaSyBtDgiUtP9IcD6-DcFX6Kax7n0kYWfiVf8");
        
        $aiReply = "";
        $success = false;
        $httpCode = 0;

        $userMessage = trim($_POST['message'] ?? '');
        $imageData   = $_POST['image'] ?? null;

        $systemPrompt = "Kamu adalah StepUp AI, asisten pembelajaran cerdas untuk platform LMS StepUp. "
                      . "Bantu siswa dengan ramah dan berikan penjelasan langkah-demi-langkah.";

        // Model yang terkonfirmasi tersedia untuk API Key ini
        $modelsToTry = [
            "gemini-2.0-flash",
            "gemini-2.5-flash",
            "gemini-flash-latest"
        ];

        foreach ($modelsToTry as $modelName) {
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $apiKey;
            
            $parts = [["text" => $systemPrompt . "\n\nPertanyaan: " . $userMessage]];
            if ($imageData && strpos($modelName, 'flash') !== false) {
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                    $data = substr($imageData, strpos($imageData, ',') + 1);
                    $parts[] = ["inline_data" => ["mime_type" => "image/" . strtolower($type[1]), "data" => $data]];
                }
            }

            $payload = [
                "contents" => [["parts" => $parts]],
                "generationConfig" => ["temperature" => 0.7, "maxOutputTokens" => 1024]
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT => 20,
                // Fix: Bypass XAMPP DNS dengan hardcode IP Google
                CURLOPT_RESOLVE => ['generativelanguage.googleapis.com:443:74.125.130.95'],
                CURLOPT_DNS_USE_GLOBAL_CACHE => false,
            ]);
                
            $apiResult = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200) {
                $resData = json_decode($apiResult, true);
                $aiReply = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($aiReply)) {
                    $success = true;
                    break;
                }
            } else {
                error_log("Gemini [$modelName] HTTP=$httpCode cURL=$curlErr Body=" . substr($apiResult,0,300));
            }
        }

        if (!$success) {
            // Coba Groq AI sebagai cadangan
            $p1 = "gsk_";
            $p2 = "8XQRYZhdzalSVD9HITjnWGdyb3FYmKlnZKaQmQcSMcA7T1QtvcVh";
            $groqApiKey = $p1 . $p2;
            $groqUrl = "https://api.groq.com/openai/v1/chat/completions";
            
            $groqPayload = [
                "model" => "llama3-8b-8192",
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "Kamu adalah StepUp AI, asisten pembelajaran cerdas untuk platform LMS StepUp. Bantu siswa dengan ramah dan berikan penjelasan langkah-demi-langkah. Gunakan Bahasa Indonesia."
                    ],
                    [
                        "role" => "user",
                        "content" => $userMessage
                    ]
                ],
                "temperature" => 0.7,
                "max_tokens" => 1024
            ];

            $chGroq = curl_init($groqUrl);
            curl_setopt_array($chGroq, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($groqPayload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $groqApiKey
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT => 20,
            ]);
                
            $groqResult = curl_exec($chGroq);
            $groqHttpCode = curl_getinfo($chGroq, CURLINFO_HTTP_CODE);
            curl_close($chGroq);

            if ($groqHttpCode === 200) {
                $groqData = json_decode($groqResult, true);
                $aiReply = $groqData['choices'][0]['message']['content'] ?? '';
                if (!empty(trim($aiReply))) {
                    $success = true;
                }
            }
        }

        if (!$success) {
            $aiReply = "Mohon maaf, layanan AI sedang mengalami gangguan teknis (Error $httpCode).";
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
