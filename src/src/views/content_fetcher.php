<?php
// src/views/content_fetcher.php
error_reporting(0);
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) exit;
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if (!$msg) { echo "<!--JSON_START-->" . json_encode(['success'=>false]) . "<!--JSON_END-->"; exit; }

    $apiKeys = [
        "AIzaSyAiHmF0AL35HzqR_mTVCybvr-3wiPMm23c",
        "AIzaSyA7nI9mvuRoHFmDIWZOCKsnLB51EUvimV4",
        "AIzaSyCI-HTcL7EzJYeO-pBnclp9kSCpLQzzuho",
        "AIzaSyDvLIgfdnpbnL7biDayxCV-ywBLrolF3F4",
    ];
    shuffle($apiKeys);

    // Expanded list: newest models first
    $attempts = [
        ['model'=>'gemini-2.0-flash-exp',     'api'=>'v1beta'],
        ['model'=>'gemini-2.0-flash',          'api'=>'v1beta'],
        ['model'=>'gemini-2.0-flash',          'api'=>'v1'],
        ['model'=>'gemini-1.5-flash-latest',   'api'=>'v1beta'],
        ['model'=>'gemini-1.5-flash',          'api'=>'v1beta'],
        ['model'=>'gemini-1.5-flash',          'api'=>'v1'],
        ['model'=>'gemini-1.5-pro',            'api'=>'v1beta'],
        ['model'=>'gemini-pro',                'api'=>'v1beta'],
        ['model'=>'gemini-pro',                'api'=>'v1'],
    ];

    $success = false;
    $reply   = '';

    foreach ($apiKeys as $key) {
        if (strlen($key) < 20) continue;
        foreach ($attempts as $att) {
            $url = "https://generativelanguage.googleapis.com/{$att['api']}/models/{$att['model']}:generateContent?key={$key}";
            $payload = json_encode([
                "contents" => [["parts"=>[["text"=>"Kamu adalah Asisten Belajar StepUp. Jawab sebagai teman belajar yang ramah dan cerdas. Pakai Bahasa Indonesia. Pertanyaan: ".$msg]]]],
                "generationConfig" => ["temperature"=>0.8,"maxOutputTokens"=>800]
            ]);
            $ch = curl_init($url);
            curl_setopt_array($ch,[
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_POST=>true,
                CURLOPT_POSTFIELDS=>$payload,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
                CURLOPT_SSL_VERIFYPEER=>false,
                CURLOPT_TIMEOUT=>15,
            ]);
            $res  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code!==200) continue;
            $data = json_decode($res, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $reply   = $data['candidates'][0]['content']['parts'][0]['text'];
                $success = true;
                break 2;
            }
        }
    }

    if (!$success) {
        $reply = "Wah, sepertinya robot saya sedang istirahat atau API Key sudah kadaluarsa 😅. Coba cek kembali di Google AI Studio ya!";
    }

    try {
        $pdo->prepare("INSERT INTO chat_logs (user_id, message, response) VALUES (?,?,?)")
            ->execute([$_SESSION['user_id'], $msg, $reply]);
    } catch(Exception $e){}

    echo "<!--JSON_START-->" . json_encode(['success'=>true,'reply'=>$reply]) . "<!--JSON_END-->";

} else {
    try {
        $stmt = $pdo->prepare("SELECT message, response FROM chat_logs WHERE user_id=? ORDER BY id DESC LIMIT 15");
        $stmt->execute([$_SESSION['user_id']]);
        $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "<!--JSON_START-->" . json_encode(['success'=>true,'history'=>$history]) . "<!--JSON_END-->";
    } catch(Exception $e) {
        echo "<!--JSON_START-->" . json_encode(['success'=>false]) . "<!--JSON_END-->";
    }
}
