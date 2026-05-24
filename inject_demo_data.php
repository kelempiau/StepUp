<?php
require 'config/db.php';

// First, find a valid topic to inject modules into
$stmt = $pdo->query("SELECT id, slug FROM topics LIMIT 1");
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    echo "No topics found. Create a topic first.\n";
    exit;
}

$topic_id = $topic['id'];
$topic_slug = $topic['slug'];

$modules = [
    [
        'title' => 'Pengenalan Machine Learning dengan Python',
        'slug' => 'pengenalan-machine-learning-python-demo',
        'content' => 'Dalam modul ini, kita akan mempelajari dasar-dasar Machine Learning menggunakan bahasa pemrograman Python. Machine learning adalah cabang dari kecerdasan buatan (AI) yang memungkinkan sistem untuk belajar dan berkembang dari pengalaman tanpa diprogram secara eksplisit.',
        'video_url' => 'https://www.youtube.com/embed/GwIoAwb4_r8',
        'duration_minutes' => 45,
        'materials' => [
            ['title' => 'Tujuan Pembelajaran', 'content' => '<p>Setelah menyelesaikan modul ini, siswa diharapkan mampu:</p><ul><li>Memahami konsep dasar Machine Learning.</li><li>Mengenal library Python seperti Scikit-Learn dan Pandas.</li><li>Membangun model prediksi sederhana.</li></ul>'],
            ['title' => 'Apa itu Machine Learning?', 'content' => '<p>Machine Learning (ML) adalah ilmu yang membuat komputer bertindak tanpa diprogram secara eksplisit. Alih-alih menulis aturan (rules), kita memberikan data kepada algoritma ML untuk dipelajari polanya.</p><br><p>Terdapat 3 tipe utama Machine Learning:</p><ol><li><strong>Supervised Learning:</strong> Belajar dari data yang memiliki label (contoh: memprediksi harga rumah).</li><li><strong>Unsupervised Learning:</strong> Belajar dari data tanpa label untuk menemukan struktur tersembunyi (contoh: segmentasi pelanggan).</li><li><strong>Reinforcement Learning:</strong> Belajar melalui sistem *reward* dan *punishment*.</li></ol>']
        ],
        'questions' => [
            ['question' => 'Apa tujuan utama dari Supervised Learning?', 'a' => 'Mencari struktur tersembunyi dalam data', 'b' => 'Memprediksi output berdasarkan data berlabel', 'c' => 'Belajar melalui trial and error', 'd' => 'Menghapus data yang tidak relevan', 'ans' => 'B', 'explanation' => 'Supervised learning menggunakan data latih yang memiliki label untuk memprediksi output pada data baru.'],
            ['question' => 'Library Python manakah yang paling sering digunakan untuk implementasi Machine Learning secara umum?', 'a' => 'Django', 'b' => 'Flask', 'c' => 'Scikit-Learn', 'd' => 'BeautifulSoup', 'ans' => 'C', 'explanation' => 'Scikit-Learn adalah library standar de-facto untuk algoritma ML klasik di Python.'],
            ['question' => 'Sistem rekomendasi YouTube merupakan contoh aplikasi dari...', 'a' => 'Machine Learning', 'b' => 'Web Scraping', 'c' => 'Database Management', 'd' => 'Game Development', 'ans' => 'A', 'explanation' => 'YouTube menggunakan algoritma ML kompleks untuk mempelajari preferensi pengguna dan merekomendasikan video yang relevan.']
        ]
    ]
];

foreach ($modules as $m) {
    // Insert Module
    $stmt = $pdo->prepare("INSERT INTO modules (topic_id, topic_slug, title, slug, content, video_url, video_type, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, 'youtube', ?)");
    try {
        $stmt->execute([$topic_id, $topic_slug, $m['title'], $m['slug'], $m['content'], $m['video_url'], $m['duration_minutes']]);
        $module_id = $pdo->lastInsertId();
        
        // Insert Materials
        foreach ($m['materials'] as $mat) {
            $pdo->prepare("INSERT INTO module_materials (module_id, title, content) VALUES (?, ?, ?)")->execute([$module_id, $mat['title'], $mat['content']]);
        }
        
        // Insert Questions
        foreach ($m['questions'] as $q) {
            $pdo->prepare("INSERT INTO questions (module_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([$module_id, $q['question'], $q['a'], $q['b'], $q['c'], $q['d'], $q['ans'], $q['explanation']]);
        }
        echo "Successfully injected: " . $m['title'] . "\n";
    } catch (Exception $e) {
        echo "Failed to inject " . $m['title'] . ": " . $e->getMessage() . "\n";
    }
}
?>
