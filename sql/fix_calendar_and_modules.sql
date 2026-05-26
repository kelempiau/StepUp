-- 1. FIX AGENDA KALENDER ERROR (Ubah type jadi category)
ALTER TABLE calendar_events CHANGE COLUMN type category VARCHAR(50) DEFAULT 'TUGAS';

-- 2. ISI MODUL KOSONG UNTUK SEMUA MATA PELAJARAN
-- Menambahkan Topik
INSERT IGNORE INTO topics (id, subject_id, subject_slug, slug, title) VALUES
(101, 1, 'matematika', 'aljabar-dasar', 'Aljabar Dasar'),
(102, 2, 'pkn', 'pancasila-dasar', 'Pancasila sebagai Dasar Negara'),
(103, 3, 'seni-budaya', 'seni-rupa-2d', 'Seni Rupa 2 Dimensi'),
(104, 5, 'bahasa-indonesia', 'teks-cerpen', 'Memahami Teks Cerpen'),
(105, 7, 'bahasa-inggris', 'narrative-text', 'Narrative Text Fundamentals'),
(106, 8, 'ipa', 'sistem-pencernaan', 'Sistem Pencernaan Manusia'),
(107, 9, 'ips', 'sejarah-kemerdekaan', 'Sejarah Kemerdekaan RI'),
(108, 10, 'sejarah', 'perang-dunia-2', 'Dampak Perang Dunia II');

-- Menambahkan Modul ke Topik
INSERT IGNORE INTO modules (id, topic_id, topic_slug, slug, title) VALUES
(201, 101, 'aljabar-dasar', 'pengenalan-variabel', 'Pengenalan Variabel dan Persamaan'),
(202, 102, 'pancasila-dasar', 'sejarah-pancasila', 'Sejarah Perumusan Pancasila'),
(203, 103, 'seni-rupa-2d', 'unsur-seni', 'Unsur-Unsur Seni Rupa'),
(204, 104, 'teks-cerpen', 'struktur-cerpen', 'Struktur dan Unsur Intrinsik Cerpen'),
(205, 105, 'narrative-text', 'reading-narrative', 'Reading & Understanding Narrative'),
(206, 106, 'sistem-pencernaan', 'organ-pencernaan', 'Organ-Organ Pencernaan'),
(207, 107, 'sejarah-kemerdekaan', 'proklamasi', 'Peristiwa Proklamasi 1945'),
(208, 108, 'perang-dunia-2', 'akhir-perang', 'Berakhirnya Perang Dunia II di Asia');

-- Menambahkan Materi ke Modul
INSERT IGNORE INTO module_materials (module_id, title, content) VALUES
(201, 'Pengenalan Variabel', 'Variabel adalah simbol yang mewakili angka...'),
(202, 'Sejarah Pancasila', 'Pancasila dirumuskan dalam sidang BPUPKI...'),
(203, 'Unsur Seni Rupa', 'Garis, bidang, bentuk, warna, tekstur...'),
(204, 'Struktur Cerpen', 'Abstrak, Orientasi, Komplikasi, Resolusi...'),
(205, 'Reading Narrative', 'Orientation, Complication, Resolution...'),
(206, 'Organ Pencernaan', 'Mulut, Kerongkongan, Lambung, Usus...'),
(207, 'Peristiwa Proklamasi', 'Dibacakan oleh Ir. Soekarno pada 17 Agustus 1945...'),
(208, 'Berakhirnya PD II', 'Bom atom di Hiroshima dan Nagasaki...');
