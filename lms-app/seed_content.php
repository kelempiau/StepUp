<?php
// d:\PSAJ MTK\lms-app\seed_content.php
require_once __DIR__ . '/config/db.php';

echo "<h1>Seeding Data: Bahasa Inggris, IPA, IPS</h1>";

$data = [
    'Bahasa Inggris' => [
        'slug' => 'bahasa-inggris',
        'topics' => [
            [
                'title' => 'Tenses Mastery',
                'modules' => [
                    ['title' => 'Simple Present Tense', 'video' => 'https://www.youtube.com/embed/L9AWrRFhsQ4'],
                    ['title' => 'Simple Past Tense', 'video' => 'https://www.youtube.com/embed/m9bK87oU99A'],
                    ['title' => 'Present Continuous Tense', 'video' => 'https://www.youtube.com/embed/Vb1-y5NhmI0'],
                ]
            ],
            [
                'title' => 'Reading Comprehension',
                'modules' => [
                    ['title' => 'Narrative Text', 'video' => 'https://www.youtube.com/embed/G_bX8I99rN8'],
                    ['title' => 'Descriptive Text', 'video' => 'https://www.youtube.com/embed/D3sR8t8_sN0'],
                    ['title' => 'Recount Text', 'video' => 'https://www.youtube.com/embed/8v_z9hO_Eow'],
                    ['title' => 'Procedure Text', 'video' => 'https://www.youtube.com/embed/a8A_T3P2X8s'],
                ]
            ],
            [
                'title' => 'Grammar & Structure',
                'modules' => [
                    ['title' => 'Nouns and Pronouns', 'video' => 'https://www.youtube.com/embed/2-nO7gAuv7w'],
                    ['title' => 'Adjectives and Adverbs', 'video' => 'https://www.youtube.com/embed/q_2mXQ_Qxqw'],
                    ['title' => 'Prepositions', 'video' => 'https://www.youtube.com/embed/Vz5z_kZl_18'],
                ]
            ],
            [
                'title' => 'Speaking & Expressions',
                'modules' => [
                    ['title' => 'Greetings and Introductions', 'video' => 'https://www.youtube.com/embed/Fw0RdTHVQI0'],
                    ['title' => 'Asking and Giving Directions', 'video' => 'https://www.youtube.com/embed/DpBlyi9y0Qk'],
                    ['title' => 'Expressing Opinions', 'video' => 'https://www.youtube.com/embed/a9h8659gHgk'],
                ]
            ]
        ]
    ],
    'IPA' => [
        'slug' => 'ipa',
        'topics' => [
            [
                'title' => 'Biologi Sel',
                'modules' => [
                    ['title' => 'Struktur dan Fungsi Sel', 'video' => 'https://www.youtube.com/embed/URUJD5NEXC8'],
                    ['title' => 'Organel Sel Hewan dan Tumbuhan', 'video' => 'https://www.youtube.com/embed/8IlzKri08kk'],
                    ['title' => 'Transport Membran', 'video' => 'https://www.youtube.com/embed/dPKvHrD1eS4'],
                ]
            ],
            [
                'title' => 'Ekosistem dan Lingkungan',
                'modules' => [
                    ['title' => 'Komponen Ekosistem', 'video' => 'https://www.youtube.com/embed/v6ubvF2iB_Q'],
                    ['title' => 'Jaring-jaring Makanan', 'video' => 'https://www.youtube.com/embed/Vtb3IHRAAQA'],
                    ['title' => 'Dampak Pencemaran Lingkungan', 'video' => 'https://www.youtube.com/embed/OqqNhmjI0iY'],
                ]
            ],
            [
                'title' => 'Fisika Dasar',
                'modules' => [
                    ['title' => 'Hukum Newton', 'video' => 'https://www.youtube.com/embed/kKKM8Y-u7ds'],
                    ['title' => 'Usaha dan Energi', 'video' => 'https://www.youtube.com/embed/w4QnZroH8H8'],
                    ['title' => 'Gelombang dan Bunyi', 'video' => 'https://www.youtube.com/embed/0N4R2X3m7_k'],
                ]
            ],
            [
                'title' => 'Tata Surya',
                'modules' => [
                    ['title' => 'Karakteristik Planet', 'video' => 'https://www.youtube.com/embed/libKVRa01L8'],
                    ['title' => 'Gerhana Matahari dan Bulan', 'video' => 'https://www.youtube.com/embed/cxrLRbkOwKs'],
                    ['title' => 'Hukum Kepler', 'video' => 'https://www.youtube.com/embed/5a1z9m3s_9o'],
                ]
            ]
        ]
    ],
    'IPS' => [
        'slug' => 'ips',
        'topics' => [
            [
                'title' => 'Sejarah Indonesia',
                'modules' => [
                    ['title' => 'Masa Praaksara', 'video' => 'https://www.youtube.com/embed/3Q9D_n3B1-A'],
                    ['title' => 'Kerajaan Hindu-Buddha', 'video' => 'https://www.youtube.com/embed/9BqM2B0zOEI'],
                    ['title' => 'Perjuangan Kemerdekaan', 'video' => 'https://www.youtube.com/embed/7V-0Jv4Yh9A'],
                ]
            ],
            [
                'title' => 'Geografi Benua',
                'modules' => [
                    ['title' => 'Karakteristik Benua Asia', 'video' => 'https://www.youtube.com/embed/0k9QvY_7L0Y'],
                    ['title' => 'Benua Eropa dan Amerika', 'video' => 'https://www.youtube.com/embed/2K1qXk_n3uU'],
                    ['title' => 'Dinamika Penduduk Dunia', 'video' => 'https://www.youtube.com/embed/1v1Z8z9qPqM'],
                ]
            ],
            [
                'title' => 'Ekonomi Dasar',
                'modules' => [
                    ['title' => 'Permintaan dan Penawaran', 'video' => 'https://www.youtube.com/embed/8v_z9hO_Eow'],
                    ['title' => 'Sistem Perbankan Indonesia', 'video' => 'https://www.youtube.com/embed/Fw0RdTHVQI0'],
                    ['title' => 'Inflasi dan Dampaknya', 'video' => 'https://www.youtube.com/embed/DpBlyi9y0Qk'],
                ]
            ],
            [
                'title' => 'Sosiologi',
                'modules' => [
                    ['title' => 'Interaksi Sosial', 'video' => 'https://www.youtube.com/embed/a9h8659gHgk'],
                    ['title' => 'Konflik dan Integrasi', 'video' => 'https://www.youtube.com/embed/URUJD5NEXC8'],
                    ['title' => 'Lembaga Sosial', 'video' => 'https://www.youtube.com/embed/8IlzKri08kk'],
                ]
            ]
        ]
    ]
];

function generateContent($subject_name, $topic_title, $module_title) {
    return "
        <h2>Materi: {$module_title}</h2>
        <p>Selamat datang di modul <strong>{$module_title}</strong> untuk mata pelajaran <strong>{$subject_name}</strong> (Topik: {$topic_title}).</p>
        <p>Pada materi ini, kita akan membahas hal-hal mendasar yang perlu dipahami dengan baik. Pastikan Anda menonton video penjelasan di atas sebelum melanjutkan membaca rangkuman di bawah ini.</p>
        
        <h3>Poin-Poin Penting:</h3>
        <ul>
            <li>Memahami konsep dasar dari {$module_title}.</li>
            <li>Mengidentifikasi ciri-ciri dan karakteristik utamanya.</li>
            <li>Menerapkan prinsip {$module_title} dalam kehidupan sehari-hari maupun soal latihan.</li>
        </ul>

        <h3>Penjelasan Detail</h3>
        <p>Secara umum, konsep ini sangat berkaitan erat dengan berbagai materi lanjutan. Oleh karena itu, penguasaan pada tahap ini bersifat krusial. Beberapa elemen yang sering muncul dalam ujian juga berasal dari pemahaman konsep {$module_title}.</p>
        
        <p><strong>Tips Belajar:</strong> Berlatihlah menggunakan contoh soal nyata dan lakukan review secara berkala. Jangan ragu untuk mencatat bagian-bagian yang dirasa sulit.</p>
        <p>Lanjutkan ke bagian kuis untuk menguji pemahaman Anda!</p>
    ";
}

function generateQuestions($module_title) {
    return [
        [
            'q' => "Apa yang menjadi fokus utama dari materi {$module_title}?",
            'opts' => ["Pemahaman konsep dasar", "Hafalan rumus", "Membaca teks panjang", "Menulis esai"],
            'ans' => 0
        ],
        [
            'q' => "Berikut ini merupakan manfaat mempelajari {$module_title}, KECUALI...",
            'opts' => ["Meningkatkan nilai ujian", "Menambah wawasan", "Menghilangkan stres belajar", "Membuat bingung"],
            'ans' => 3
        ],
        [
            'q' => "Langkah pertama yang paling tepat untuk menguasai materi {$module_title} adalah...",
            'opts' => ["Mengerjakan soal sulit langsung", "Menonton video dan membaca poin penting", "Tidur", "Mengabaikan materi"],
            'ans' => 1
        ],
        [
            'q' => "Konsep {$module_title} paling relevan diaplikasikan pada...",
            'opts' => ["Kehidupan sehari-hari dan latihan soal", "Bermain game", "Olahraga", "Tidur siang"],
            'ans' => 0
        ]
    ];
}

foreach ($data as $subject_name => $subj_data) {
    // Check if subject exists
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE title LIKE ? OR slug = ?");
    $stmt->execute(["%$subject_name%", $subj_data['slug']]);
    $subject = $stmt->fetch();

    if (!$subject) {
        echo "<p style='color:red;'>Mata Pelajaran: {$subject_name} tidak ditemukan. Memasukkan secara otomatis...</p>";
        $stmt = $pdo->prepare("INSERT INTO subjects (title, slug, description, bg_pattern, cover_color, icon, is_active) VALUES (?, ?, 'Materi lengkap', 'p1', '#3B82F6', 'fas fa-book', 1)");
        $stmt->execute([$subject_name, $subj_data['slug']]);
        $subject_id = $pdo->lastInsertId();
    } else {
        $subject_id = $subject['id'];
        echo "<p style='color:green;'>Mata Pelajaran: {$subject_name} ditemukan (ID: $subject_id).</p>";
    }

    foreach ($subj_data['topics'] as $topic) {
        $topic_title = $topic['title'];
        $topic_slug = strtolower(str_replace(' ', '-', $topic_title));

        // Check topic
        $stmt = $pdo->prepare("SELECT id FROM topics WHERE subject_id = ? AND title = ?");
        $stmt->execute([$subject_id, $topic_title]);
        $topic_row = $stmt->fetch();

        if (!$topic_row) {
            $stmt = $pdo->prepare("INSERT INTO topics (subject_id, subject_slug, slug, title) VALUES (?, ?, ?, ?)");
            $stmt->execute([$subject_id, $subj_data['slug'], $topic_slug, $topic_title]);
            $topic_id = $pdo->lastInsertId();
            echo "<ul><li>Menambahkan Topik: {$topic_title}</li></ul>";
        } else {
            $topic_id = $topic_row['id'];
        }

        foreach ($topic['modules'] as $module) {
            $mod_title = $module['title'];
            $mod_slug = strtolower(str_replace(' ', '-', $mod_title));
            $mod_video = $module['video'];

            // Check module
            $stmt = $pdo->prepare("SELECT id FROM modules WHERE topic_id = ? AND title = ?");
            $stmt->execute([$topic_id, $mod_title]);
            $mod_row = $stmt->fetch();

            if (!$mod_row) {
                $stmt = $pdo->prepare("INSERT INTO modules (topic_id, topic_slug, slug, title, video_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$topic_id, $topic_slug, $mod_slug, $mod_title, $mod_video]);
                $mod_id = $pdo->lastInsertId();
                echo "<ul style='margin-left: 40px;'><li>Menambahkan Modul: {$mod_title}</li></ul>";

                // Add material
                $contentHtml = generateContent($subject_name, $topic_title, $mod_title);
                $stmt = $pdo->prepare("INSERT INTO module_materials (module_id, title, content) VALUES (?, 'Materi Utama', ?)");
                $stmt->execute([$mod_id, $contentHtml]);

                // Add questions
                $questions = generateQuestions($mod_title);
                foreach ($questions as $q) {
                    $stmt = $pdo->prepare("INSERT INTO module_questions (module_id, question, options, answer) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$mod_id, $q['q'], json_encode($q['opts']), $q['ans']]);
                }
            }
        }
    }
}

echo "<h2>Sukses! Semua data telah ditambahkan.</h2>";
?>
