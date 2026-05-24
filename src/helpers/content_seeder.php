<?php
// src/helpers/content_seeder.php
require_once __DIR__ . '/../../config/db.php';

function seed() {
    global $pdo;

    // 1. Fill Materials for more modules
    $materials = [
        [56, 'Hukum Permintaan & Penawaran', 'Permintaan adalah jumlah barang yang ingin dibeli konsumen pada berbagai tingkat harga. Penawaran adalah jumlah barang yang ditawarkan produsen. Titik temu keduanya disebut Harga Keseimbangan.'],
        [57, 'Kebijakan Ekonomi', 'Kebijakan Moneter diatur oleh Bank Sentral untuk mengontrol jumlah uang beredar. Kebijakan Fiskal diatur oleh Pemerintah melalui pajak dan pengeluaran negara.'],
        [69, 'Historiografi', 'Historiografi adalah tahap penulisan sejarah. Ada tiga jenis: Tradisional (sentris raja), Kolonial (sentris Belanda), dan Modern (sentris Indonesia/ilmiah).'],
        [70, 'Manfaat Sejarah', 'Sejarah memiliki manfaat Edukatif (belajar dari masa lalu), Inspiratif (memberi semangat), dan Rekreatif (sebagai hiburan melalui kisah nyata).'],
        [119, 'Revolusi Pertanian', 'Masa bercocok tanam ditandai dengan perubahan dari Food Gathering (mencari makanan) menjadi Food Producing (menghasilkan makanan) dan mulai hidup menetap (Sedenter).']
    ];

    foreach ($materials as $m) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO module_materials (module_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute($m);
    }

    // 2. Add some Quiz Questions for these modules
    $questions = [
        // Module 56
        [56, 'Apa yang terjadi jika harga barang naik menurut hukum permintaan?', ['Jumlah permintaan turun', 'Jumlah permintaan naik', 'Permintaan tetap', 'Barang hilang'], 0],
        [56, 'Siapa yang melakukan penawaran di pasar?', ['Produsen', 'Konsumen', 'Pemerintah', 'Pencuri'], 0],
        // Module 57
        [57, 'Siapa yang berwenang mengatur kebijakan moneter di Indonesia?', ['Bank Indonesia', 'Presiden', 'DPR', 'Menteri Keuangan'], 0],
        // Module 69
        [69, 'Historiografi yang bersifat Belanda-sentris disebut...', ['Historiografi Kolonial', 'Historiografi Tradisional', 'Historiografi Modern', 'Historiografi Kuno'], 0]
    ];

    foreach ($questions as $q) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO module_questions (module_id, question, options, answer) VALUES (?, ?, ?, ?)");
        $stmt->execute([$q[0], $q[1], json_encode($q[2]), $q[3]]);
    }

    echo "Content seeded successfully.\n";
}

seed();
