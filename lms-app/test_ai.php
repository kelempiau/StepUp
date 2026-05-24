<?php
// test_ai.php - Diagnosa AI Gemini
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnosa Koneksi AI StepUp</h2>";

$apiKey = "AIzaSyAiHmF0AL35HzqR_mTVCybvr-3wiPMm23c"; // Salah satu kunci Anda
$model = "gemini-1.5-flash";
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;

$payload = [
    "contents" => [["parts" => [["text" => "Katakan 'Koneksi Berhasil' jika kamu menerima ini."]]]]
];

echo "Mencoba menghubungi Google Gemini via CURL...<br>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<b>HTTP Code:</b> " . $httpCode . "<br>";
if ($curlError) {
    echo "<b>CURL Error:</b> " . $curlError . "<br>";
}
echo "<b>Raw Response:</b><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200) {
    echo "<h3>✅ KONEKSI BERHASIL!</h3>";
    echo "Jika ini berhasil, berarti masalahnya ada di database atau cara pengiriman pesan dari fitur Chat.";
} else {
    echo "<h3>❌ KONEKSI GAGAL!</h3>";
    echo "Pesan error di atas menjelaskan alasannya (biasanya 429=Limit Habis, 400=Request Salah, 403=Key Salah).";
}
?>
