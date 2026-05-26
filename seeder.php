<?php
// Script Seeder Otomatis untuk Mengisi Modul & Soal StepUp
require_once __DIR__ . '/config/db.php';

echo "Memulai Seeder Otomatis...\n";

// Definisi data pelajaran, topik, dan soal
// Kita akan menambahkan setidaknya 1 topik dan 1 modul untuk mata pelajaran yang masih kosong.
// Mapel ID: 1(Matematika), 2(PKN), 3(Seni Budaya), 5(Bahasa Indonesia), 7(Bahasa Inggris), 8(IPA), 9(IPS), 10(Sejarah)

$seed_data = [
    1 => [ // Matematika
        'topic' => ['slug' => 'aljabar', 'title' => 'Aljabar Lanjutan'],
        'module' => ['slug' => 'persamaan-kuadrat', 'title' => 'Persamaan Kuadrat', 'content' => 'Persamaan kuadrat adalah persamaan polinomial orde dua...'],
        'question' => ['q' => 'Bentuk umum persamaan kuadrat adalah...', 'a' => 'ax^2 + bx + c = 0', 'b' => 'ax + b = 0', 'c' => 'x^3 = 0', 'd' => 'y = mx + c', 'correct' => 'A']
    ],
    2 => [ // PKN
        'topic' => ['slug' => 'pancasila', 'title' => 'Memahami Pancasila'],
        'module' => ['slug' => 'sejarah-pancasila', 'title' => 'Sejarah Lahirnya Pancasila', 'content' => 'Pancasila lahir pada tanggal 1 Juni 1945 melalui pidato Bung Karno...'],
        'question' => ['q' => 'Kapan hari lahirnya Pancasila?', 'a' => '17 Agustus', 'b' => '1 Juni', 'c' => '28 Oktober', 'd' => '10 November', 'correct' => 'B']
    ],
    3 => [ // Seni Budaya
        'topic' => ['slug' => 'seni-rupa', 'title' => 'Seni Rupa 2 Dimensi'],
        'module' => ['slug' => 'unsur-seni', 'title' => 'Unsur-unsur Seni Rupa', 'content' => 'Seni rupa memiliki beberapa unsur utama seperti titik, garis, bidang, bentuk...'],
        'question' => ['q' => 'Manakah yang BUKAN merupakan unsur seni rupa?', 'a' => 'Titik', 'b' => 'Garis', 'c' => 'Suara', 'd' => 'Bentuk', 'correct' => 'C']
    ],
    5 => [ // Bahasa Indonesia
        'topic' => ['slug' => 'teks-cerpen', 'title' => 'Teks Cerita Pendek'],
        'module' => ['slug' => 'unsur-intrinsik', 'title' => 'Unsur Intrinsik Cerpen', 'content' => 'Unsur intrinsik cerpen meliputi tema, alur, tokoh, latar, dan amanat...'],
        'question' => ['q' => 'Pesan moral dalam cerita disebut...', 'a' => 'Tema', 'b' => 'Alur', 'c' => 'Latar', 'd' => 'Amanat', 'correct' => 'D']
    ],
    7 => [ // Bahasa Inggris
        'topic' => ['slug' => 'narrative', 'title' => 'Narrative Text'],
        'module' => ['slug' => 'structure', 'title' => 'Structure of Narrative Text', 'content' => 'The structure consists of Orientation, Complication, and Resolution...'],
        'question' => ['q' => 'The beginning of a narrative text is called...', 'a' => 'Resolution', 'b' => 'Orientation', 'c' => 'Complication', 'd' => 'Reorientation', 'correct' => 'B']
    ],
    8 => [ // IPA
        'topic' => ['slug' => 'sistem-pencernaan', 'title' => 'Sistem Pencernaan Manusia'],
        'module' => ['slug' => 'organ-pencernaan', 'title' => 'Organ-organ Pencernaan', 'content' => 'Makanan masuk melalui mulut, kemudian ke kerongkongan, lambung, usus halus, usus besar...'],
        'question' => ['q' => 'Pencernaan mekanik di mulut dibantu oleh...', 'a' => 'Lidah dan Gigi', 'b' => 'Asam Lambung', 'c' => 'Enzim Ptialin', 'd' => 'Usus', 'correct' => 'A']
    ],
    9 => [ // IPS
        'topic' => ['slug' => 'sejarah-kemerdekaan', 'title' => 'Sejarah Kemerdekaan'],
        'module' => ['slug' => 'proklamasi', 'title' => 'Peristiwa Proklamasi', 'content' => 'Proklamasi dibacakan oleh Ir. Soekarno pada 17 Agustus 1945 di Jalan Pegangsaan Timur...'],
        'question' => ['q' => 'Siapa yang membacakan teks proklamasi?', 'a' => 'Moh Hatta', 'b' => 'Ir. Soekarno', 'c' => 'Sutan Sjahrir', 'd' => 'Ahmad Subardjo', 'correct' => 'B']
    ],
    10 => [ // Sejarah
        'topic' => ['slug' => 'perang-dunia-2', 'title' => 'Perang Dunia II'],
        'module' => ['slug' => 'akhir-perang', 'title' => 'Akhir Perang di Pasifik', 'content' => 'Perang di Pasifik berakhir dengan jatuhnya bom atom di Hiroshima dan Nagasaki...'],
        'question' => ['q' => 'Kota di Jepang yang dijatuhi bom atom adalah...', 'a' => 'Tokyo dan Osaka', 'b' => 'Hiroshima dan Nagasaki', 'c' => 'Kyoto dan Kobe', 'd' => 'Fukuoka dan Sapporo', 'correct' => 'B']
    ]
];

try {
    foreach ($seed_data as $subject_id => $data) {
        // Cek apakah subject ada
        $check = $pdo->prepare("SELECT id, slug FROM subjects WHERE id = ?");
        $check->execute([$subject_id]);
        $subject = $check->fetch();
        if (!$subject) {
            echo "Mapel ID $subject_id tidak ada, skip.\n";
            continue;
        }

        // 1. Insert Topic
        $pdo->prepare("INSERT IGNORE INTO topics (subject_id, subject_slug, slug, title) VALUES (?, ?, ?, ?)")
            ->execute([$subject_id, $subject['slug'], $data['topic']['slug'], $data['topic']['title']]);
        
        $topic_id = $pdo->query("SELECT id FROM topics WHERE subject_id = $subject_id AND slug = '{$data['topic']['slug']}'")->fetchColumn();

        // 2. Insert Module
        $pdo->prepare("INSERT IGNORE INTO modules (topic_id, topic_slug, slug, title) VALUES (?, ?, ?, ?)")
            ->execute([$topic_id, $data['topic']['slug'], $data['module']['slug'], $data['module']['title']]);
            
        $module_id = $pdo->query("SELECT id FROM modules WHERE topic_id = $topic_id AND slug = '{$data['module']['slug']}'")->fetchColumn();

        // 3. Insert Material
        $pdo->prepare("INSERT IGNORE INTO module_materials (module_id, title, content) VALUES (?, ?, ?)")
            ->execute([$module_id, $data['module']['title'], $data['module']['content']]);

        // 4. Create Challenge (Quiz) for this topic
        $pdo->prepare("INSERT IGNORE INTO challenges (title, description, points, difficulty, is_active, week_type) VALUES (?, ?, 10, 'medium', 1, 'current')")
            ->execute(['Tantangan Kuis: ' . $data['module']['title'], 'Uji pemahamanmu tentang ' . $data['topic']['title']]);
            
        $challenge_id = $pdo->lastInsertId();
        if (!$challenge_id) {
            $challenge_id = $pdo->query("SELECT id FROM challenges WHERE title = 'Tantangan Kuis: {$data['module']['title']}' ORDER BY id DESC LIMIT 1")->fetchColumn();
        }

        // 5. Insert Question
        $pdo->prepare("INSERT IGNORE INTO challenge_questions (challenge_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$challenge_id, $data['question']['q'], $data['question']['a'], $data['question']['b'], $data['question']['c'], $data['question']['d'], $data['question']['correct']]);

        echo "Berhasil mengisi materi & soal untuk mata pelajaran: " . $subject['slug'] . "\n";
    }
    
    echo "SEEDING SELESAI!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
