<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$activePage   = 'scan';
$pageTitle    = 'Scan QR Barang';
$pageSubtitle = 'IDENTIFIKASI CEPAT';
$userRole     = $_SESSION['user']['role'] ?? 'peminjam';
$userId       = (int) $_SESSION['user']['id'];

// ============================================
// ENDPOINT AJAX: AMBIL DATA BARANG DARI QR
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'get_item') {
    header('Content-Type: application/json; charset=utf-8');

    $itemId = (int) ($_GET['id'] ?? 0);
    if ($itemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID barang tidak valid.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            b.id, b.nama_barang, b.kategori, b.jumlah, b.kondisi,
            COALESCE(SUM(CASE WHEN p.status = 'dipinjam' THEN p.jumlah ELSE 0 END), 0) AS sedang_dipinjam,
            GREATEST(0, b.jumlah - COALESCE(SUM(CASE WHEN p.status = 'dipinjam' THEN p.jumlah ELSE 0 END), 0)) AS sisa_tersedia
        FROM barang b
        LEFT JOIN peminjaman p ON p.barang_id = b.id AND p.status = 'dipinjam'
        WHERE b.id = :id
        GROUP BY b.id
    ");
    $stmt->execute(['id' => $itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Barang dengan ID #' . $itemId . ' tidak ditemukan.']);
        exit;
    }

    $sudahAda = false;
    $cekAktif = $pdo->prepare("
        SELECT COUNT(*) FROM peminjaman 
        WHERE user_id = :uid AND barang_id = :bid AND status IN ('menunggu persetujuan','disetujui','dipinjam')
    ");
    $cekAktif->execute(['uid' => $userId, 'bid' => $itemId]);
    $sudahAda = $cekAktif->fetchColumn() > 0;

    echo json_encode([
        'success' => true,
        'item' => [
            'id'              => (int) $item['id'],
            'nama_barang'     => $item['nama_barang'],
            'kategori'        => $item['kategori'],
            'kondisi'         => $item['kondisi'],
            'total_jumlah'    => (int) $item['jumlah'],
            'sedang_dipinjam' => (int) $item['sedang_dipinjam'],
            'sisa_tersedia'   => (int) $item['sisa_tersedia'],
            'sudah_ada'       => $sudahAda,
            'is_admin'        => ($userRole === 'admin'),
        ]
    ]);
    exit;
}

// ============================================
// PROSES PENGAJUAN LANGSUNG DARI FLOATING CARD
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajukan_scan') {
    verify_csrf();

    $barangId    = (int) ($_POST['barang_id'] ?? 0);
    $jumlah      = max(1, (int) ($_POST['jumlah'] ?? 1));
    $tglPinjam   = date('Y-m-d');
    $tglKembali  = trim((string) ($_POST['tanggal_kembali'] ?? ''));

    // Validasi barang & stok
    $stmt = $pdo->prepare("
        SELECT b.id, b.nama_barang, b.jumlah,
               COALESCE(SUM(CASE WHEN p.status = 'dipinjam' THEN p.jumlah ELSE 0 END), 0) AS sedang_dipinjam
        FROM barang b
        LEFT JOIN peminjaman p ON p.barang_id = b.id AND p.status = 'dipinjam'
        WHERE b.id = :id
        GROUP BY b.id
    ");
    $stmt->execute(['id' => $barangId]);
    $barang = $stmt->fetch();

    $sisa = $barang ? max(0, $barang['jumlah'] - $barang['sedang_dipinjam']) : 0;

    if (!$barang) {
        flash('error', 'Barang tidak ditemukan.');
        header('Location: scan.php');
        exit;
    }

    if (empty($tglKembali) || $tglKembali <= $tglPinjam) {
        flash('error', 'Tanggal rencana pengembalian harus setelah hari ini.');
        header('Location: scan.php');
        exit;
    }

    if ($jumlah > $sisa || $sisa <= 0) {
        flash('error', 'Jumlah peminjaman (' . $jumlah . ' unit) melebihi sisa stok yang tersedia (' . $sisa . ' unit).');
        header('Location: scan.php');
        exit;
    }

    // Insert pengajuan
    $ins = $pdo->prepare("
        INSERT INTO peminjaman (user_id, barang_id, jumlah, tanggal_pinjam, tanggal_kembali, status)
        VALUES (:uid, :bid, :jumlah, :tgl_pinjam, :tgl_kembali, 'menunggu persetujuan')
    ");
    $ins->execute([
        'uid'         => $userId,
        'bid'         => $barangId,
        'jumlah'      => $jumlah,
        'tgl_pinjam'  => $tglPinjam,
        'tgl_kembali' => $tglKembali,
    ]);

    flash('success', 'Pengajuan ' . $jumlah . ' unit "' . $barang['nama_barang'] . '" berhasil dikirim! Menunggu persetujuan Admin.');
    header('Location: ../peminjaman/index.php');
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SIMPEL MEDIA KOMINFO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        #reader { width: 100% !important; border: none !important; }
        #reader video { width: 100% !important; height: auto !important; max-height: 480px; object-fit: contain !important; border-radius: 1rem; }
        #reader img { display: none !important; }
        #reader div[style*="border"] { border: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col">

        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main class="flex-1 p-4 md:p-6 space-y-4">

            <div class="max-w-xl mx-auto space-y-4">

                <!-- KARTU SCANNER UTAMA -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    
                    <!-- Header Card -->
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                                <i data-lucide="scan-qr-code" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-800">Scan QR Code Barang</h2>
                                <p class="text-xs text-slate-400">Arahkan kamera ke QR Code pada peralatan</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Input Upload Gambar QR -->
                            <input type="file" id="inputQrFile" accept="image/*" class="hidden" onchange="prosesUploadGambar(event)">
                            <button type="button" onclick="document.getElementById('inputQrFile').click()" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition flex items-center gap-1.5" title="Unggah file foto QR">
                                <i data-lucide="image" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span class="hidden sm:inline">Unggah Foto</span>
                            </button>

                            <!-- Input ID Manual -->
                            <button type="button" onclick="bukaModalManual()" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition flex items-center gap-1.5">
                                <i data-lucide="keyboard" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span class="hidden sm:inline">Input ID</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">

                        <!-- STATUS INDIKATOR & SENTER -->
                        <div class="flex items-center justify-between gap-2 bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 mb-4">
                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <span id="scanStatusDot" class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                <span id="scanStatusText">Kamera aktif — Siap memindai...</span>
                            </div>

                            <button type="button" id="btnToggleTorch" onclick="toggleTorch()" class="hidden text-xs font-semibold text-slate-600 hover:text-amber-500 items-center gap-1 transition">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>Senter</span>
                            </button>
                        </div>

                        <!-- AREA KAMERA / VIEWFINDER -->
                        <div class="relative w-full bg-slate-900 rounded-2xl overflow-hidden shadow-inner border border-slate-200 min-h-[300px] flex items-center justify-center">
                            
                            <div id="reader" class="w-full"></div>

                            <!-- CARD IZIN KAMERA (JIKA BELUM DIIZINKAN) -->
                            <div id="cameraPermissionPrompt" class="hidden absolute inset-0 z-20 bg-white/95 backdrop-blur-xs p-6 flex flex-col items-center justify-center text-center">
                                <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                                    <i data-lucide="camera" class="w-7 h-7"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 mb-1">Izin Kamera Diperlukan</h3>
                                <p class="text-xs text-slate-500 mb-4 max-w-xs leading-relaxed">
                                    Izinkan akses kamera browser Anda untuk memindai QR Code peralatan.
                                </p>
                                <button type="button" onclick="startScannerAuto()" class="px-5 py-2.5 rounded-xl bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold text-xs shadow-sm transition flex items-center gap-2">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                    <span>Aktifkan Kamera</span>
                                </button>
                            </div>
                        </div>

                        <!-- TOMBOL KONTROL BAWAH -->
                        <div class="flex items-center justify-between gap-3 mt-4 pt-3 border-t border-slate-100 flex-wrap">
                            <span class="text-[11px] text-slate-400 font-medium">Dekatkan QR Code ke kamera hingga fokus</span>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btnSwitchCamera" onclick="switchCamera()" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-blue-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition" title="Ganti Kamera">
                                    <i data-lucide="switch-camera" class="w-3.5 h-3.5"></i>
                                    <span>Ganti Kamera</span>
                                </button>
                                <button type="button" id="btnRestartCamera" onclick="restartScanner()" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-blue-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                    <span>Muat Ulang</span>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </main>

        <?php require __DIR__ . '/../includes/footer.php'; ?>

    </div>

    <!-- DIV HIDDEN UNTUK SCANNER FILE UPLOAD -->
    <div id="file-scanner-temp" class="hidden"></div>

    <!-- ============================================ -->
    <!-- FLOATING CARD MENGAMBANG (BOTTOM SHEET / MODAL) -->
    <!-- ============================================ -->
    <div id="floatingCardBackdrop" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs hidden items-end sm:items-center justify-center p-0 sm:p-4 transition-all">
        <div id="floatingCard" class="bg-white text-slate-800 w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden transition-transform duration-200 transform translate-y-full sm:translate-y-0">
            
            <!-- Handlebar (Mobile gesture bar) -->
            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto my-3 sm:hidden"></div>

            <!-- STATUS BANNER -->
            <div id="modalStatusBanner" class="bg-emerald-500 text-white px-5 py-3 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
                <span id="modalStatusBannerText" class="font-bold text-sm">Tersedia untuk Dipinjam</span>
                <span id="modalSisaTersediaBadge" class="ml-auto font-bold text-emerald-100 text-sm">0 unit</span>
            </div>

            <!-- KONTEN INFO BARANG -->
            <div class="p-5 max-h-[80vh] overflow-y-auto space-y-4">
                
                <!-- Info Header -->
                <div class="flex items-start gap-3.5">
                    <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center shrink-0">
                        <i data-lucide="package" class="w-7 h-7 text-slate-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 id="modalNamaBarang" class="text-base font-bold text-slate-900 leading-tight">Nama Barang</h2>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                            <span id="modalKategoriBadge" class="inline-flex items-center gap-1 text-[11px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">
                                Kategori
                            </span>
                            <span id="modalKondisiBadge" class="inline-flex items-center gap-1 text-[11px] font-semibold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span id="modalKondisiText">Baik</span>
                            </span>
                        </div>
                    </div>
                    <button type="button" onclick="tutupFloatingCard()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- 3 STATS BOX (IDENTIK DENGAN SCREENSHOT) -->
                <div class="grid grid-cols-3 gap-2.5">
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-2.5 text-center">
                        <div id="statTotalUnit" class="text-xl font-extrabold text-slate-800">0</div>
                        <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-wider">Total Unit</div>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-2.5 text-center">
                        <div id="statDipinjam" class="text-xl font-extrabold text-amber-600">0</div>
                        <div class="text-[10px] text-amber-600 font-bold mt-0.5 uppercase tracking-wider">Dipinjam</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2.5 text-center">
                        <div id="statTersedia" class="text-xl font-extrabold text-emerald-600">0</div>
                        <div class="text-[10px] text-emerald-600 font-bold mt-0.5 uppercase tracking-wider">Tersedia</div>
                    </div>
                </div>

                <!-- FORM PENGAJUAN PINJAM -->
                <form id="formAjukanScan" method="post" action="scan.php" class="space-y-3.5 pt-1">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="ajukan_scan">
                    <input type="hidden" name="barang_id" id="modalInputBarangId" value="0">

                    <!-- Pesan jika sudah ada pengajuan -->
                    <div id="modalAlertSudahAda" class="hidden flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 font-semibold py-3 px-3.5 rounded-xl text-xs">
                        <i data-lucide="clock" class="w-4 h-4 shrink-0 text-amber-500"></i>
                        <span>Anda sudah memiliki pengajuan aktif untuk barang ini.</span>
                    </div>

                    <!-- Input Fields (hanya tampil jika belum ada pengajuan dan stok > 0) -->
                    <div id="modalFormFields" class="space-y-3.5">
                        
                        <!-- Stepper Jumlah -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Jumlah Unit yang Dipinjam
                            </label>
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="ubahJumlahModal(-1)" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg flex items-center justify-center transition shrink-0">−</button>
                                <input type="number" id="modalInputJumlah" name="jumlah" value="1" min="1" max="1" class="flex-1 text-center font-extrabold text-lg border border-slate-200 rounded-xl py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <button type="button" onclick="ubahJumlahModal(1)" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg flex items-center justify-center transition shrink-0">+</button>
                            </div>
                            <p id="modalMaxKet" class="text-[10px] text-slate-400 text-center mt-1">Maks. 0 unit tersedia</p>
                        </div>

                        <!-- Tanggal Pengembalian -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                📅 Rencana Tanggal Pengembalian *
                            </label>
                            <input 
                                type="date" 
                                id="modalInputTglKembali" 
                                name="tanggal_kembali" 
                                min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                required
                                class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                            >
                        </div>

                        <!-- Tombol Submit -->
                        <button type="submit" id="btnSubmitModal" class="w-full flex items-center justify-center gap-2 bg-[#102b4e] hover:bg-[#1a3d6b] text-white font-bold py-3 rounded-xl transition text-xs shadow-sm">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Kirim Pengajuan Peminjaman</span>
                        </button>
                    </div>

                    <!-- Tombol Tutup / Scan Ulang -->
                    <button type="button" onclick="tutupFloatingCard()" class="w-full py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-700 transition text-center">
                        Batal &amp; Pindai Barang Lain
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- MODAL INPUT ID MANUAL -->
    <div id="modalManualInput" class="app-modal fixed inset-0 z-50 bg-black/60 backdrop-blur-xs hidden items-center justify-center p-4">
        <div class="bg-white text-slate-800 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-sm font-bold text-slate-800">Cari Barang Manual</h3>
                <button type="button" onclick="tutupModalManual()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="prosesManualId(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">ID Barang / Inventaris</label>
                    <input type="number" id="manualBarangId" placeholder="Contoh: 1, 2, 5" min="1" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="tutupModalManual()" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-[#102b4e] text-white hover:bg-[#1a3d6b] transition">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT LOGIKA PEMINDAI & FLOATING CARD -->
    <script>
    let html5QrScanner = null;
    let isProcessing = false;
    let currentSisaStok = 0;
    let torchActive = false;
    let currentCameraFacing = 'environment';

    // Beep audio effect
    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.12);
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
        startScannerAuto();
    });

    function setScannerStatus(text, color = 'emerald') {
        const dot = document.getElementById('scanStatusDot');
        const label = document.getElementById('scanStatusText');
        label.textContent = text;
        dot.className = 'w-2.5 h-2.5 rounded-full inline-block ' + (color === 'emerald' ? 'bg-emerald-500 animate-pulse' : (color === 'amber' ? 'bg-amber-500' : 'bg-blue-500 animate-ping'));
    }

    function startScannerAuto() {
        document.getElementById('cameraPermissionPrompt').classList.add('hidden');
        setScannerStatus('Menghubungkan ke kamera...', 'blue');

        if (html5QrScanner) {
            try { html5QrScanner.stop(); } catch(e) {}
        }

        html5QrScanner = new Html5Qrcode('reader', {
            formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ],
            verbose: false
        });

        // Config tanpa qrbox kecil agar mendeteksi di SELURUH layar kamera dengan sensitivitas penuh!
        const config = {
            fps: 20,
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            }
        };

        html5QrScanner.start(
            { facingMode: currentCameraFacing },
            config,
            (decodedText) => {
                if (isProcessing) return;
                onQrCodeScanned(decodedText);
            },
            () => {}
        ).then(() => {
            setScannerStatus('Kamera aktif — Dekatkan QR Code ke kamera', 'emerald');
            checkTorch();
        }).catch((err) => {
            // Jika environment gagal (misal di laptop webcam), coba camera user
            if (currentCameraFacing === 'environment') {
                currentCameraFacing = 'user';
                html5QrScanner.start(
                    { facingMode: 'user' },
                    config,
                    (decodedText) => {
                        if (isProcessing) return;
                        onQrCodeScanned(decodedText);
                    },
                    () => {}
                ).then(() => {
                    setScannerStatus('Kamera aktif — Dekatkan QR Code ke kamera', 'emerald');
                }).catch((err2) => {
                    console.warn('Gagal buka kamera:', err2);
                    document.getElementById('cameraPermissionPrompt').classList.remove('hidden');
                    setScannerStatus('Kamera belum diizinkan', 'amber');
                });
            } else {
                console.warn('Gagal buka kamera:', err);
                document.getElementById('cameraPermissionPrompt').classList.remove('hidden');
                setScannerStatus('Kamera belum diizinkan', 'amber');
            }
        });
    }

    function switchCamera() {
        currentCameraFacing = (currentCameraFacing === 'environment') ? 'user' : 'environment';
        restartScanner();
    }

    function restartScanner() {
        if (html5QrScanner) {
            html5QrScanner.stop().then(() => {
                startScannerAuto();
            }).catch(() => {
                startScannerAuto();
            });
        } else {
            startScannerAuto();
        }
    }

    function checkTorch() {
        try {
            const track = html5QrScanner?.getRunningTrackCameraCapabilities();
            if (track && track.torchFeature().isSupported()) {
                document.getElementById('btnToggleTorch').classList.remove('hidden');
                document.getElementById('btnToggleTorch').classList.add('inline-flex');
            }
        } catch(e) {}
    }

    function toggleTorch() {
        try {
            torchActive = !torchActive;
            html5QrScanner?.applyVideoConstraints({
                advanced: [{ torch: torchActive }]
            });
            const btn = document.getElementById('btnToggleTorch');
            if (torchActive) {
                btn.classList.add('text-amber-500');
            } else {
                btn.classList.remove('text-amber-500');
            }
        } catch(e) {}
    }

    // Parsing ID barang dari hasil scan yang sangat toleran
    function onQrCodeScanned(rawText) {
        if (isProcessing) return;
        isProcessing = true;
        playBeep();
        setScannerStatus('QR terdeteksi! Mengambil data...', 'blue');

        let barangId = 0;
        // Tangkap id dari URL misal detail.php?id=5 atau format angka langsung
        const match = rawText.match(/[?&]id=(\d+)/i) || rawText.match(/\bid[:=]?\s*(\d+)/i) || rawText.match(/^(\d+)$/);
        if (match) {
            barangId = parseInt(match[1]);
        }

        if (barangId <= 0) {
            alert('QR Code terbaca: "' + rawText + '"\nNamun tidak ditemukan ID barang yang cocok.');
            isProcessing = false;
            setScannerStatus('Kamera aktif — Siap memindai QR Code', 'emerald');
            return;
        }

        ambilDataBarang(barangId);
    }

    // Scan lewat file gambar yang diupload
    function prosesUploadGambar(e) {
        const file = e.target.files[0];
        if (!file) return;

        setScannerStatus('Membaca file QR...', 'blue');
        const fileScanner = new Html5Qrcode('file-scanner-temp');
        fileScanner.scanFile(file, true)
            .then(decodedText => {
                fileScanner.clear();
                onQrCodeScanned(decodedText);
            })
            .catch(err => {
                fileScanner.clear();
                alert('Gambar tidak dapat terbaca sebagai QR Code. Pastikan foto QR cukup jelas dan tidak buram.');
                setScannerStatus('Kamera aktif — Siap memindai QR Code', 'emerald');
            });
    }

    function ambilDataBarang(barangId) {
        fetch('scan.php?action=get_item&id=' + barangId)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Barang tidak ditemukan.');
                    isProcessing = false;
                    setScannerStatus('Kamera aktif — Siap memindai QR Code', 'emerald');
                    return;
                }
                bukaFloatingCard(data.item);
            })
            .catch(err => {
                alert('Gagal mengambil data barang: ' + err);
                isProcessing = false;
                setScannerStatus('Kamera aktif — Siap memindai QR Code', 'emerald');
            });
    }

    function bukaFloatingCard(item) {
        currentSisaStok = item.sisa_tersedia;

        document.getElementById('modalInputBarangId').value = item.id;
        document.getElementById('modalNamaBarang').textContent = item.nama_barang;
        document.getElementById('modalKategoriBadge').textContent = item.kategori;
        document.getElementById('modalKondisiText').textContent = item.kondisi;
        document.getElementById('statTotalUnit').textContent = item.total_jumlah;
        document.getElementById('statDipinjam').textContent = item.sedang_dipinjam;
        document.getElementById('statTersedia').textContent = item.sisa_tersedia;
        document.getElementById('modalMaxKet').textContent = 'Maks. ' + item.sisa_tersedia + ' unit tersedia';

        const banner = document.getElementById('modalStatusBanner');
        const bannerText = document.getElementById('modalStatusBannerText');
        const badge = document.getElementById('modalSisaTersediaBadge');
        const formFields = document.getElementById('modalFormFields');
        const alertSudahAda = document.getElementById('modalAlertSudahAda');
        const inputJumlah = document.getElementById('modalInputJumlah');

        inputJumlah.max = Math.max(1, item.sisa_tersedia);
        inputJumlah.value = 1;

        if (item.sisa_tersedia > 0) {
            banner.className = 'bg-emerald-500 text-white px-5 py-3 flex items-center gap-2';
            bannerText.textContent = 'Tersedia untuk Dipinjam';
            badge.textContent = item.sisa_tersedia + ' unit';
            badge.className = 'ml-auto font-bold text-emerald-100 text-sm';
        } else {
            banner.className = 'bg-rose-500 text-white px-5 py-3 flex items-center gap-2';
            bannerText.textContent = 'Semua Unit Sedang Dipinjam';
            badge.textContent = 'Habis';
            badge.className = 'ml-auto font-bold text-rose-100 text-sm';
        }

        if (item.sudah_ada) {
            alertSudahAda.classList.remove('hidden');
            formFields.classList.add('hidden');
        } else if (item.sisa_tersedia <= 0 || item.is_admin) {
            alertSudahAda.classList.add('hidden');
            formFields.classList.add('hidden');
        } else {
            alertSudahAda.classList.add('hidden');
            formFields.classList.remove('hidden');
        }

        const backdrop = document.getElementById('floatingCardBackdrop');
        const card = document.getElementById('floatingCard');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
        setTimeout(() => {
            card.classList.remove('translate-y-full');
            card.classList.add('translate-y-0');
        }, 20);

        lucide.createIcons();
    }

    function ubahJumlahModal(delta) {
        const input = document.getElementById('modalInputJumlah');
        const max = currentSisaStok > 0 ? currentSisaStok : 1;
        let val = parseInt(input.value || 1) + delta;
        if (val < 1) val = 1;
        if (val > max) val = max;
        input.value = val;
    }

    function tutupFloatingCard() {
        const backdrop = document.getElementById('floatingCardBackdrop');
        const card = document.getElementById('floatingCard');
        card.classList.remove('translate-y-0');
        card.classList.add('translate-y-full');
        setTimeout(() => {
            backdrop.classList.remove('flex');
            backdrop.classList.add('hidden');
            isProcessing = false;
            setScannerStatus('Kamera aktif — Siap memindai QR Code', 'emerald');
        }, 200);
    }

    function bukaModalManual() {
        const modal = document.getElementById('modalManualInput');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('manualBarangId').focus();
    }

    function tutupModalManual() {
        const modal = document.getElementById('modalManualInput');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function prosesManualId(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('manualBarangId').value);
        if (id <= 0) return;
        tutupModalManual();
        ambilDataBarang(id);
    }
    </script>
</body>
</html>
