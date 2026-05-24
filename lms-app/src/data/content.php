<?php
// src/data/content.php

/**
 * Content Generator for StepUp LMS
 * Generates structure for 7 Subjects x 10 Topics x 20 Modules.
 */

// 1. Define Subjects and their Topics (Real names for Math, Patterns for others)
// Function to generate the massive data structure
function generateStepUpContent() {
    $subjects_config = [
        'matematika' => [
            'title' => 'Matematika (Wajib)',
            'icon' => '<i class="fas fa-calculator text-blue-500"></i>',
            'description' => 'Konsep dasar hingga lanjutan matematika kelas 12.',
            'topics' => [
                'Dimensi Tiga: Jarak dalam Ruang',
                'Dimensi Tiga: Sudut dalam Ruang',
                'Statistika: Penyajian Data',
                'Statistika: Ukuran Pemusatan Data',
                'Statistika: Ukuran Penyebaran Data',
                'Kaidah Pencacahan',
                'Peluang Kejadian Majemuk',
                'Limit Fungsi Trigonometri',
                'Turunan Fungsi Trigonometri',
                'Aplikasi Turunan Fungsi'
            ]
        ],

        'pancasila' => [
            'title' => 'Pendidikan Pancasila',
            'icon' => '<i class="fas fa-dove text-yellow-500"></i>',
            'description' => 'Memperkuat wawasan kebangsaan dan kewarganegaraan.',
            'topics' => [
                'Pelanggaran Hak dan Pengingkaran Kewajiban',
                'Perlindungan dan Penegakan Hukum',
                'Pengaruh Kemajuan IPTEK',
                'Persatik dan Kesatuan Bangsa',
                'Nilai-nilai Pancasila',
                'Demokrasi Pancasila',
                'Sistem Hukum dan Peradilan',
                'Dinamika Persatuan Bangsa',
                'Wawasan Nusantara',
                'Konstitusi Negara'
            ]
        ],
        'indonesia' => [
            'title' => 'Bahasa Indonesia',
            'icon' => '<i class="fas fa-feather-alt text-red-500"></i>',
            'description' => 'Menguasai literasi dan kebahasaan tingkat lanjut.',
            'topics' => [
                'Surat Lamaran Pekerjaan',
                'Teks Cerita Sejarah',
                'Teks Editorial',
                'Novel',
                'Artikel',
                'Kritik dan Esai',
                'Drama dan Teater',
                'Buku Fiksi dan Non-Fiksi',
                'Analisis Kebahasaan',
                'Menulis Karya Ilmiah'
            ]
        ],
        'inggris' => [
            'title' => 'Bahasa Inggris',
            'icon' => '<i class="fas fa-language text-blue-400"></i>',
            'description' => 'Enhancing English proficiency for global communication.',
            'topics' => [
                'Offering Help & Services',
                'Application Letter',
                'Caption Text',
                'News Item Text',
                'Conditional Sentences',
                'Procedural Text',
                'Analytical Exposition',
                'Discussion Text',
                'Songs and Poems',
                'Review Text'
            ]
        ],
        'sejarah' => [
            'title' => 'Sejarah Indonesia',
            'icon' => '<i class="fas fa-landmark text-amber-600"></i>',
            'description' => 'Menelusuri jejak peristiwa penting bangsa.',
            'topics' => [
                'Perjuangan Menghadapi Disintegrasi',
                'Sistem Pemerintahan Liberal',
                'Demokrasi Terpimpin',
                'Orde Baru',
                'Reformasi',
                'Peran Indonesia dalam Perdamaian Dunia',
                'Perkembangan IPTEK di Indonesia',
                'Revolusi Hijau',
                'Organisasi Regional dan Global',
                'Tokoh Pahlawan Nasional'
            ]
        ],
        'pjok' => [
            'title' => 'PJOK',
            'icon' => '<i class="fas fa-running text-orange-500"></i>',
            'description' => 'Menjaga kesehatan dan kebugaran jasmani.',
            'topics' => [
                'Taktik Permainan Sepak Bola',
                'Strategi Permainan Bola Voli',
                'Taktik Permainan Bola Basket',
                'Strategi Permainan Bulu Tangkis',
                'Taktik Permainan Tenis Meja',
                'Atletik: Lari Jarak Pendek',
                'Atletik: Lompat Jauh',
                'Pencak Silat',
                'Kebugaran Jasmani',
                'Penyakit Menular Seksual'
            ]
        ]
    ];

    $final_content = [];

    foreach ($subjects_config as $subjSlug => $config) {
        $final_content[$subjSlug] = [
            'title' => $config['title'],
            'icon' => $config['icon'],
            'description' => $config['description'],
            'topics' => [] 
        ];

        foreach ($config['topics'] as $tIndex => $topicName) {
            $topicSlug = 'topic-' . ($tIndex + 1);
            $modules = [];

            // Generate 20 Modules per Topic
            for ($m = 1; $m <= 20; $m++) {
                $modSlug = 'modul-' . $m;
                
                // Very Long Content Generator
                $lorem = "
                    <div class='space-y-6'>
                        <div class='aspect-w-16 aspect-h-9 mb-6'>
                            <iframe src='https://www.youtube.com/embed/D0UnqGm_miA' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen class='w-full h-96 rounded-xl shadow-lg'></iframe>
                        </div>

                        <h2 class='text-2xl font-bold text-slate-800'>$topicName - Modul $m</h2>
                        <p class='text-slate-600 leading-relaxed'>
                            Selamat datang di modul pembelajaran ke-$m untuk topik <strong>$topicName</strong>. 
                            Dalam modul ini, kita akan mendalami konsep secara komprehensif. Materi ini dirancang untuk memberikan pemahaman mendalam bagi siswa kelas 12.
                        </p>

                        <div class='bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-xl'>
                            <h3 class='font-bold text-blue-800 text-lg mb-2'>Tujuan Pembelajaran</h3>
                            <ul class='list-disc list-inside text-blue-700 space-y-1'>
                                <li>Memahami definisi dan konsep dasar $topicName.</li>
                                <li>Menganalisis studi kasus terkait modul ke-$m.</li>
                                <li>Menerapkan teori dalam penyelesaian masalah kompleks.</li>
                            </ul>
                        </div>

                        <h3 class='text-xl font-bold text-slate-800'>Pendahuluan Teori</h3>
                        <p class='text-slate-600 leading-relaxed'>
                            Secara teoritis, pembahasan ini melibatkan banyak variabel. Mari kita bayangkan sebuah situasi di mana...
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
                            Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                        </p>
                        <p class='text-slate-600 leading-relaxed'>
                            Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. 
                            Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                        </p>

                        <h3 class='text-xl font-bold text-slate-800'>Analisis Mendalam</h3>
                        <p class='text-slate-600 leading-relaxed'>
                            Melanjutkan dari pendahuluan, aspek kritis yang perlu diperhatikan adalah bagaimana interaksi antar komponen terjadi. 
                            Dalam konteks $topicName, hal ini sangat relevan.
                        </p>
                        <ul class='list-disc pl-5 space-y-2 text-slate-600'>
                            <li><strong>Poin A:</strong> Penjelasan mendetail mengenai poin A yang mencakup definisi dan contoh.</li>
                            <li><strong>Poin B:</strong> Elaborasi mengenai poin B dan hubungannya dengan poin A.</li>
                            <li><strong>Poin C:</strong> Kesimpulan sementara dari kedua poin di atas.</li>
                        </ul>

                         <h3 class='text-xl font-bold text-slate-800'>Kesimpulan</h3>
                        <p class='text-slate-600 leading-relaxed'>
                             Sebagai penutup modul ke-$m ini, penting untuk merefleksikan kembali apa yang telah dipelajari. 
                             Penguasaan materi ini adalah fondasi untuk modul selanjutnya.
                        </p>
                    </div>
                ";

                // Quiz Generator (20 Questions)
                $quiz = [];
                for ($q = 1; $q <= 20; $q++) {
                    $quiz[] = [
                        'q' => "Soal Evaluasi No. $q: Apa inti dari pembahasan $topicName pada bagian modul $m?",
                        'options' => [
                            'Opsi A: Ini adalah jawaban yang paling benar sesuai teori.',
                            'Opsi B: Ini adalah jawaban pengecoh yang mirip.',
                            'Opsi C: Ini adalah jawaban yang jelas salah.',
                            'Opsi D: Ini adalah jawaban yang tidak relevan.'
                        ],
                        'answer' => 0
                    ];
                }

                $materials = [
                    [
                        'title' => "Materi Utama",
                        'content' => $lorem
                    ]
                ];

                $modules[$modSlug] = [
                    'title' => "Modul $m",
                    'materials' => $materials,
                    'quiz' => $quiz
                ];
            }

            $final_content[$subjSlug]['topics'][$topicSlug] = [
                'title' => $topicName,
                'modules' => $modules
            ];
        }
    }
    
    return $final_content;
}

$content_data = generateStepUpContent();
?>
