<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/functions.php';
require_admin();

$pageTitle = 'Laporan';
$dari   = DateTime::createFromFormat('Y-m-d', $_GET['dari'] ?? '') ? $_GET['dari'] : date('Y-m-01');
$sampai = DateTime::createFromFormat('Y-m-d', $_GET['sampai'] ?? '') ? $_GET['sampai'] : date('Y-m-d');
$peran  = in_array($_GET['peran'] ?? '', ['Guru', 'Siswa'], true) ? $_GET['peran'] : '';

$where  = 'WHERE c.tanggal BETWEEN ? AND ?' . ($peran ? ' AND u.level=?' : '');
$params = $peran ? [$dari, $sampai, $peran] : [$dari, $sampai];

/* Rekap per pengguna */
$st = $pdo->prepare('SELECT COALESCE(u.nama,s.nama,\'-\') AS nama,u.level,s.kelas,COUNT(DISTINCT c.id_checklist) sesi,
    SUM(d.status=\'Ya\') ya,SUM(d.status=\'Tidak\') tidak,MAX(c.tanggal) terakhir
    FROM checklist c LEFT JOIN users u ON u.id_user=c.id_user LEFT JOIN siswa s ON s.nis=c.nis
    LEFT JOIN detail_checklist d ON d.id_checklist=c.id_checklist '.$where.'
    GROUP BY c.id_user,u.nama,s.nama,u.level,s.kelas ORDER BY sesi DESC,nama');
$st->execute($params); $perUser = $st->fetchAll();

/* Rekap per tanggal */
$st = $pdo->prepare('SELECT c.tanggal,COUNT(DISTINCT c.id_checklist) sesi,SUM(d.status=\'Ya\') ya,SUM(d.status=\'Tidak\') tidak
    FROM checklist c LEFT JOIN users u ON u.id_user=c.id_user LEFT JOIN detail_checklist d ON d.id_checklist=c.id_checklist '.$where.'
    GROUP BY c.tanggal ORDER BY c.tanggal DESC');
$st->execute($params); $perTanggal = $st->fetchAll();

/* Rekap per tahapan */
$st = $pdo->prepare('SELECT d.tahapan,COUNT(*) total,SUM(d.status=\'Ya\') ya,SUM(d.status=\'Tidak\') tidak
    FROM checklist c LEFT JOIN users u ON u.id_user=c.id_user JOIN detail_checklist d ON d.id_checklist=c.id_checklist '.$where.'
    GROUP BY d.tahapan ORDER BY FIELD(d.tahapan,\'SEBELUM\',\'SELAMA\',\'SESUDAH\',\'5R\')');
$st->execute($params); $perTahap = $st->fetchAll();

/* Rincian lengkap */
$st = $pdo->prepare('SELECT c.tanggal,c.jam,COALESCE(u.nama,s.nama,\'-\') AS nama,u.level,p.nama_perangkat,d.tahapan,d.kondisi,d.status,d.keterangan
    FROM checklist c LEFT JOIN users u ON u.id_user=c.id_user LEFT JOIN siswa s ON s.nis=c.nis
    JOIN detail_checklist d ON d.id_checklist=c.id_checklist JOIN perangkat p ON p.id_perangkat=d.id_perangkat '.$where.'
    ORDER BY c.tanggal DESC,c.jam DESC,d.id_detail');
$st->execute($params); $rincian = $st->fetchAll();

$totalSesi = array_sum(array_column($perTanggal, 'sesi'));
$totalYa   = array_sum(array_column($perTanggal, 'ya'));
$totalTdk  = array_sum(array_column($perTanggal, 'tidak'));
$lbl = tahapan_list();

require __DIR__.'/includes/header.php';
?>
<div class="welcome-row"><div><span class="eyebrow">LAPORAN</span><h1>Laporan Checklist</h1><p>Periode <?= e(date('d M Y', strtotime($dari))) ?> – <?= e(date('d M Y', strtotime($sampai))) ?><?= $peran ? ' · ' . e($peran) : ' · Guru &amp; Siswa' ?></p></div><button class="btn btn-primary no-print" onclick="window.print()">🖨 Cetak / Simpan PDF</button></div>

<section class="panel form-panel no-print"><form method="get"><div class="form-grid four-cols">
  <div class="form-field"><label>Dari tanggal</label><input class="form-control" type="date" name="dari" value="<?= e($dari) ?>"></div>
  <div class="form-field"><label>Sampai tanggal</label><input class="form-control" type="date" name="sampai" value="<?= e($sampai) ?>"></div>
  <div class="form-field"><label>Peran</label><select class="form-control" name="peran"><option value="">Guru &amp; Siswa</option><option <?= $peran === 'Guru' ? 'selected' : '' ?>>Guru</option><option <?= $peran === 'Siswa' ? 'selected' : '' ?>>Siswa</option></select></div>
  <div class="form-field"><label>&nbsp;</label><button class="btn btn-primary btn-full">Tampilkan</button></div>
</div></form></section>

<div class="stats-grid">
  <article class="stat-card"><div class="stat-top"><span>Sesi Checklist</span><span class="stat-icon blue">☑</span></div><strong><?= number_format($totalSesi) ?></strong><small>Dalam periode ini</small></article>
  <article class="stat-card"><div class="stat-top"><span>Jawaban “Ya”</span><span class="stat-icon green">✦</span></div><strong><?= number_format($totalYa) ?></strong><small>Pemeriksaan sesuai</small></article>
  <article class="stat-card"><div class="stat-top"><span>Jawaban “Tidak”</span><span class="stat-icon orange">!</span></div><strong><?= number_format($totalTdk) ?></strong><small>Perlu perhatian</small></article>
  <article class="stat-card"><div class="stat-top"><span>Pengguna Aktif</span><span class="stat-icon lime">♙</span></div><strong><?= count($perUser) ?></strong><small>Mengisi checklist</small></article>
</div>

<section class="panel table-panel"><div class="panel-heading"><div><h2>Rekap per pengguna</h2><p>Riwayat setiap akun dihitung terpisah.</p></div></div><div class="table-wrap"><table><thead><tr><th>Pengguna</th><th>Peran</th><th>Kelas</th><th>Sesi</th><th>Ya</th><th>Tidak</th><th>Terakhir</th></tr></thead><tbody>
<?php foreach ($perUser as $r): ?><tr><td><b><?= e($r['nama']) ?></b></td><td><?= e($r['level'] ?? '-') ?></td><td><?= e($r['kelas'] ?? '-') ?></td><td><?= (int)$r['sesi'] ?></td><td><?= (int)$r['ya'] ?></td><td><?= (int)$r['tidak'] ?></td><td><?= e(date('d M Y', strtotime($r['terakhir']))) ?></td></tr><?php endforeach; if (!$perUser): ?><tr><td colspan="7" class="table-empty">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
</tbody></table></div></section>

<div class="content-split">
<section class="panel table-panel"><div class="panel-heading"><div><h2>Rekap per tahapan</h2></div></div><div class="table-wrap"><table><thead><tr><th>Tahapan</th><th>Total</th><th>Ya</th><th>Tidak</th></tr></thead><tbody>
<?php foreach ($perTahap as $r): ?><tr><td><b><?= e($lbl[$r['tahapan']] ?? $r['tahapan']) ?></b></td><td><?= (int)$r['total'] ?></td><td><?= (int)$r['ya'] ?></td><td><?= (int)$r['tidak'] ?></td></tr><?php endforeach; if (!$perTahap): ?><tr><td colspan="4" class="table-empty">Tidak ada data.</td></tr><?php endif; ?>
</tbody></table></div></section>
<section class="panel table-panel"><div class="panel-heading"><div><h2>Rekap per tanggal</h2></div></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Sesi</th><th>Ya</th><th>Tidak</th></tr></thead><tbody>
<?php foreach ($perTanggal as $r): ?><tr><td><b><?= e(date('d M Y', strtotime($r['tanggal']))) ?></b></td><td><?= (int)$r['sesi'] ?></td><td><?= (int)$r['ya'] ?></td><td><?= (int)$r['tidak'] ?></td></tr><?php endforeach; if (!$perTanggal): ?><tr><td colspan="4" class="table-empty">Tidak ada data.</td></tr><?php endif; ?>
</tbody></table></div></section>
</div>

<section class="panel table-panel"><div class="panel-heading"><div><h2>Rincian pemeriksaan</h2><p><?= count($rincian) ?> baris pemeriksaan.</p></div></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jam</th><th>Pengguna</th><th>Perangkat</th><th>Tahapan</th><th>Kondisi</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach ($rincian as $r): ?><tr><td><?= e(date('d M Y', strtotime($r['tanggal']))) ?></td><td><?= e(substr($r['jam'], 0, 5)) ?></td><td><?= e($r['nama']) ?> <small class="muted">(<?= e($r['level'] ?? '-') ?>)</small></td><td><?= e($r['nama_perangkat']) ?></td><td><?= e($lbl[$r['tahapan']] ?? $r['tahapan']) ?></td><td><?= e($r['kondisi'] ?: '-') ?></td><td><?= e($r['status']) ?></td><td><?= e($r['keterangan'] ?: '-') ?></td></tr><?php endforeach; if (!$rincian): ?><tr><td colspan="8" class="table-empty">Tidak ada data.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
