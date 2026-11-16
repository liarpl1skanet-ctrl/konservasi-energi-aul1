<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/functions.php';
require_login();
verify_csrf();

$uid     = current_user_id();
$isAdmin = is_admin();

/* NIS siswa dibaca dari akun yang login (khusus level Siswa) — tidak perlu memilih siswa lagi. */
$nis = null;
if (is_siswa()) {
    $st = $pdo->prepare('SELECT nis FROM users WHERE id_user=?');
    $st->execute([$uid]);
    $v = $st->fetchColumn();
    $nis = $v ? (int)$v : null;
}

/* ====== SIMPAN CHECKLIST (Guru & Siswa) — otomatis tercatat atas nama akun yang login ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if ($isAdmin) { http_response_code(403); exit('Admin tidak mengisi checklist. <a href="dashboard.php">Kembali</a>'); }

    $d = DateTime::createFromFormat('Y-m-d', $_POST['tanggal'] ?? '');
    $t = DateTime::createFromFormat('H:i', $_POST['jam'] ?? '');
    $tanggal = $d ? $d->format('Y-m-d') : date('Y-m-d');
    $jam     = $t ? $t->format('H:i:s') : date('H:i:s');

    $items = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
    $rows  = [];
    foreach (array_keys(tahapan_list()) as $tahap) {
        foreach (($items[$tahap] ?? []) as $dev => $it) {
            $dev    = (int)$dev;
            $status = $it['status'] ?? '';
            if (!$dev || !in_array($status, ['Ya', 'Tidak'], true)) continue; // baris yang belum diisi dilewati
            $rows[] = [$dev, $tahap, mb_substr(trim($it['kondisi'] ?? ''), 0, 100), $status, trim($it['keterangan'] ?? '')];
        }
    }

    if (!$rows) {
        flash('Isi minimal satu pemeriksaan (pilih status Ya/Tidak pada salah satu perangkat).', 'error');
    } else {
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO checklist (id_user,nis,tanggal,jam) VALUES (?,?,?,?)');
            $st->execute([$uid, $nis, $tanggal, $jam]);
            $id  = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO detail_checklist (id_checklist,id_perangkat,tahapan,kondisi,status,keterangan) VALUES (?,?,?,?,?,?)');
            foreach ($rows as $r) { $ins->execute([$id, $r[0], $r[1], $r[2], $r[3], $r[4]]); }
            $pdo->commit();
            flash('Checklist berhasil disimpan atas nama ' . ($_SESSION['user']['nama'] ?? 'kamu') . '.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('Checklist gagal disimpan. Periksa data yang dipilih.', 'error');
        }
    }
    header('Location: dashboard.php#riwayat');
    exit;
}

$pageTitle = 'Dashboard';

if ($isAdmin) {
    /* ====== DASHBOARD ADMIN: ringkasan seluruh sistem ====== */
    $students   = (int)$pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
    $devices    = (int)$pdo->query('SELECT COUNT(*) FROM perangkat')->fetchColumn();
    $checklists = (int)$pdo->query('SELECT COUNT(*) FROM checklist')->fetchColumn();
    $details    = (int)$pdo->query("SELECT COUNT(*) FROM detail_checklist WHERE status='Ya'")->fetchColumn();
    $phase      = $pdo->query("SELECT tahapan,COUNT(*) total FROM detail_checklist WHERE status='Ya' GROUP BY tahapan")->fetchAll();
    $recent     = $pdo->query('SELECT c.id_checklist,c.tanggal,c.jam,COALESCE(u.nama,s.nama,\'-\') AS nama,u.level,s.kelas,COUNT(d.id_detail) jumlah
        FROM checklist c LEFT JOIN users u ON u.id_user=c.id_user LEFT JOIN siswa s ON s.nis=c.nis
        LEFT JOIN detail_checklist d ON d.id_checklist=c.id_checklist
        GROUP BY c.id_checklist,c.tanggal,c.jam,u.nama,s.nama,u.level,s.kelas
        ORDER BY c.tanggal DESC,c.jam DESC LIMIT 6')->fetchAll();
} else {
    /* ====== DASHBOARD GURU/SISWA: tempat checklist + riwayat milik sendiri ====== */
    $devs = $pdo->query('SELECT id_perangkat,nama_perangkat,keterangan FROM perangkat ORDER BY nama_perangkat')->fetchAll();

    $q = $pdo->prepare('SELECT COUNT(*) FROM checklist WHERE id_user=?');                 $q->execute([$uid]); $myTotal = (int)$q->fetchColumn();
    $q = $pdo->prepare("SELECT COUNT(*) FROM detail_checklist d JOIN checklist c ON c.id_checklist=d.id_checklist WHERE c.id_user=? AND d.status='Ya'"); $q->execute([$uid]); $myYa = (int)$q->fetchColumn();
    $q = $pdo->prepare('SELECT COUNT(*) FROM checklist WHERE id_user=? AND tanggal=CURDATE()'); $q->execute([$uid]); $myToday = (int)$q->fetchColumn();
    $q = $pdo->prepare('SELECT MAX(tanggal) FROM checklist WHERE id_user=?');             $q->execute([$uid]); $myLast = $q->fetchColumn();

    $q = $pdo->prepare('SELECT c.id_checklist,c.tanggal,c.jam,COUNT(d.id_detail) jumlah,SUM(d.status=\'Ya\') ya,
        GROUP_CONCAT(DISTINCT p.nama_perangkat ORDER BY p.nama_perangkat SEPARATOR \', \') perangkat,
        GROUP_CONCAT(DISTINCT d.tahapan ORDER BY FIELD(d.tahapan,\'SEBELUM\',\'SELAMA\',\'SESUDAH\',\'5R\') SEPARATOR \', \') tahap
        FROM checklist c LEFT JOIN detail_checklist d ON d.id_checklist=c.id_checklist
        LEFT JOIN perangkat p ON p.id_perangkat=d.id_perangkat
        WHERE c.id_user=? GROUP BY c.id_checklist,c.tanggal,c.jam ORDER BY c.tanggal DESC,c.jam DESC LIMIT 10');
    $q->execute([$uid]); $mine = $q->fetchAll();

    $kelas = '';
    if ($nis) { $q = $pdo->prepare('SELECT kelas FROM siswa WHERE nis=?'); $q->execute([$nis]); $kelas = (string)$q->fetchColumn(); }
}

require __DIR__.'/includes/header.php';

if ($isAdmin): ?>
<div class="welcome-row"><div><span class="eyebrow">RINGKASAN SELURUH SISTEM</span><h1>Halo, <?= e($_SESSION['user']['nama']) ?> <span class="wave">✦</span></h1><p>Pantau aktivitas checklist guru dan siswa, serta kelola data sekolah.</p></div><a class="btn btn-primary" href="checklist.php">Lihat Semua Checklist →</a></div>

<div class="stats-grid">
  <article class="stat-card"><div class="stat-top"><span>Total Siswa</span><span class="stat-icon green">♙</span></div><strong><?= number_format($students) ?></strong><small>Data siswa terdaftar</small><div class="stat-bottom"><span>●</span> Terdata di sistem</div></article>
  <article class="stat-card"><div class="stat-top"><span>Perangkat</span><span class="stat-icon lime">ϟ</span></div><strong><?= number_format($devices) ?></strong><small>Jenis perangkat tercatat</small><div class="stat-bottom"><span>●</span> Inventaris energi</div></article>
  <article class="stat-card"><div class="stat-top"><span>Checklist</span><span class="stat-icon blue">☑</span></div><strong><?= number_format($checklists) ?></strong><small>Sesi dari guru &amp; siswa</small><div class="stat-bottom"><span>●</span> Aktivitas tercatat</div></article>
  <article class="stat-card"><div class="stat-top"><span>Jawaban “Ya”</span><span class="stat-icon orange">✦</span></div><strong><?= number_format($details) ?></strong><small>Detail checklist berstatus Ya</small><div class="stat-bottom"><span>●</span> Kebiasaan positif</div></article>
</div>

<div class="dashboard-grid">
  <section class="panel chart-panel"><div class="panel-heading"><div><h2>Aktivitas Checklist</h2><p>Jumlah jawaban “Ya” berdasarkan tahapan.</p></div><span class="panel-badge">Database</span></div>
  <?php if ($phase): $max = max(array_column($phase, 'total')); ?>
    <div class="bar-chart"><?php foreach ($phase as $row): ?><div class="bar-item"><div class="bar-value"><?= (int)$row['total'] ?></div><div class="bar-track"><div class="bar-fill" style="height:<?= max(8, ((int)$row['total'] / $max) * 100) ?>%"></div></div><span><?= e($row['tahapan']) ?></span></div><?php endforeach; ?></div>
  <?php else: ?>
    <div class="empty-state"><span>▥</span><b>Belum ada aktivitas detail</b><p>Data muncul setelah guru atau siswa mengisi checklist.</p></div>
  <?php endif; ?></section>

  <section class="panel quick-panel"><div class="panel-heading"><div><h2>Kelola Data</h2><p>Akses cepat untuk Admin.</p></div></div>
    <a class="quick-link" href="perangkat.php"><span class="quick-icon">ϟ</span><span><b>Data perangkat</b><small>Tambah / ubah perangkat</small></span><i>→</i></a>
    <a class="quick-link" href="siswa.php"><span class="quick-icon">♙</span><span><b>Data siswa</b><small>Kelola daftar siswa</small></span><i>→</i></a>
    <a class="quick-link" href="pengguna.php"><span class="quick-icon">⚙</span><span><b>Pengguna</b><small>Akun Admin, Guru, Siswa</small></span><i>→</i></a>
    <a class="quick-link" href="edukasi.php"><span class="quick-icon">✳</span><span><b>Edukasi</b><small>Tips hemat energi</small></span><i>→</i></a>
  </section>
</div>

<section class="panel table-panel"><div class="panel-heading"><div><h2>Checklist Terbaru</h2><p>Checklist terakhir dari semua pengguna.</p></div><a class="text-link" href="checklist.php">Lihat semua →</a></div>
<div class="table-wrap"><table><thead><tr><th>Pengguna</th><th>Peran</th><th>Kelas</th><th>Tanggal</th><th>Jam</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr><td><div class="table-person"><span class="avatar avatar-small"><?= e(strtoupper(substr($r['nama'], 0, 1))) ?></span><b><?= e($r['nama']) ?></b></div></td><td><span class="status-pill"><?= e($r['level'] ?? '-') ?></span></td><td><?= e($r['kelas'] ?? '-') ?></td><td><?= e(date('d M Y', strtotime($r['tanggal']))) ?></td><td><?= e(substr($r['jam'], 0, 5)) ?></td><td><?= (int)$r['jumlah'] ?> item</td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="6" class="table-empty">Belum ada checklist.</td></tr><?php endif; ?>
</tbody></table></div></section>

<?php else: ?>
<div class="welcome-row"><div><span class="eyebrow">DASHBOARD <?= e(strtoupper(user_level())) ?></span><h1>Halo, <?= e($_SESSION['user']['nama']) ?> <span class="wave">✦</span></h1><p>Isi checklist energi di bawah. Hasilnya otomatis tercatat atas namamu<?= $kelas ? ' (' . e($kelas) . ')' : '' ?>.</p></div><a class="btn btn-primary" href="#checklist">+ Isi Checklist</a></div>

<div class="stats-grid">
  <article class="stat-card"><div class="stat-top"><span>Checklist Saya</span><span class="stat-icon blue">☑</span></div><strong><?= number_format($myTotal) ?></strong><small>Sesi yang sudah kamu isi</small><div class="stat-bottom"><span>●</span> Semua waktu</div></article>
  <article class="stat-card"><div class="stat-top"><span>Jawaban “Ya”</span><span class="stat-icon orange">✦</span></div><strong><?= number_format($myYa) ?></strong><small>Kebiasaan hemat energi</small><div class="stat-bottom"><span>●</span> Kebiasaan positif</div></article>
  <article class="stat-card"><div class="stat-top"><span>Hari Ini</span><span class="stat-icon green">▦</span></div><strong><?= number_format($myToday) ?></strong><small>Checklist hari ini</small><div class="stat-bottom"><span>●</span> <?= e(date('d M Y')) ?></div></article>
  <article class="stat-card"><div class="stat-top"><span>Terakhir Mengisi</span><span class="stat-icon lime">ϟ</span></div><strong style="font-size:20px"><?= $myLast ? e(date('d M Y', strtotime($myLast))) : '-' ?></strong><small><?= $myLast ? 'Tanggal checklist terakhir' : 'Belum pernah mengisi' ?></small><div class="stat-bottom"><span>●</span> Riwayatmu</div></article>
</div>

<section class="panel form-panel" id="checklist"><div class="panel-heading"><div><h2>Checklist Energi</h2><p>Catat kondisi perangkat sebelum, selama, dan sesudah digunakan.</p></div><span class="status-pill"><?= e($_SESSION['user']['nama']) ?> · <?= e(user_level()) ?></span></div>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
  <div class="form-grid"><div class="form-field"><label>Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></div><div class="form-field"><label>Jam</label><input class="form-control" type="time" name="jam" value="<?= date('H:i') ?>" required></div></div>
  <div class="section-subhead"><h3>Detail pemeriksaan</h3><p>Isi status perangkat pada tiap tahapan. Baris yang dibiarkan “Belum diisi” tidak disimpan.</p></div>
  <?php if (!$devs): ?><div class="alert alert-error">Belum ada perangkat. Minta Admin menambahkan perangkat terlebih dahulu.</div><?php endif; ?>
  <?php $no = 0; foreach (tahapan_list() as $key => $label): $no++; ?>
  <div class="check-item"><div class="check-item-title"><span class="quick-icon"><?= $no ?></span><b><?= e($label) ?></b><span class="muted">Tahap <?= $no ?> dari 4</span></div>
    <?php foreach ($devs as $d): $id = (int)$d['id_perangkat']; ?>
    <div class="form-grid four-cols">
      <div class="form-field"><label>Perangkat</label><div class="form-control" style="background:#f4f8f5"><b><?= e($d['nama_perangkat']) ?></b></div></div>
      <div class="form-field"><label>Status</label><select class="form-control" name="items[<?= e($key) ?>][<?= $id ?>][status]"><option value="">Belum diisi</option><option value="Ya">Ya</option><option value="Tidak">Tidak</option></select></div>
      <div class="form-field"><label>Kondisi</label><input class="form-control" name="items[<?= e($key) ?>][<?= $id ?>][kondisi]" placeholder="Contoh: mati"></div>
      <div class="form-field"><label>Keterangan</label><input class="form-control" name="items[<?= e($key) ?>][<?= $id ?>][keterangan]" placeholder="Opsional"></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <div class="form-actions"><button class="btn btn-primary" type="submit" <?= !$devs ? 'disabled' : '' ?>>Simpan Checklist ✓</button></div>
</form></section>

<section class="panel table-panel" id="riwayat"><div class="panel-heading"><div><h2>Riwayat Checklist Saya</h2><p>10 checklist terakhir yang kamu isi.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jam</th><th>Perangkat diperiksa</th><th>Tahapan</th><th>Jawaban Ya</th></tr></thead><tbody>
<?php foreach ($mine as $r): ?><tr><td><?= e(date('d M Y', strtotime($r['tanggal']))) ?></td><td><?= e(substr($r['jam'], 0, 5)) ?></td><td><?= e($r['perangkat'] ?? '-') ?></td><td><?= e($r['tahap'] ?? '-') ?></td><td><span class="status-pill"><?= (int)$r['ya'] ?> / <?= (int)$r['jumlah'] ?></span></td></tr><?php endforeach; ?>
<?php if (!$mine): ?><tr><td colspan="5" class="table-empty">Belum ada checklist. Isi checklist pertamamu di atas.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endif; ?>

<?php require __DIR__.'/includes/footer.php'; ?>
