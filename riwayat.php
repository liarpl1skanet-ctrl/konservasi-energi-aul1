<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/functions.php';
require_login();
if (is_admin()) { header('Location: checklist.php'); exit; } // Admin punya riwayat semua pengguna di checklist.php
$pageTitle = 'Riwayat Checklist';
$uid = current_user_id();
$lbl = tahapan_list();

$dari   = DateTime::createFromFormat('Y-m-d', $_GET['dari'] ?? '')   ? $_GET['dari']   : '';
$sampai = DateTime::createFromFormat('Y-m-d', $_GET['sampai'] ?? '') ? $_GET['sampai'] : '';
$sql = 'SELECT id_checklist,tanggal,jam FROM checklist WHERE id_user=?'; $par = [$uid];
if ($dari)   { $sql .= ' AND tanggal>=?'; $par[] = $dari; }
if ($sampai) { $sql .= ' AND tanggal<=?'; $par[] = $sampai; }
$st = $pdo->prepare($sql.' ORDER BY tanggal DESC,jam DESC LIMIT 50'); $st->execute($par);
$sesi = $st->fetchAll();

$detail = [];
if ($sesi) {
    $ids = array_column($sesi, 'id_checklist');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT d.id_checklist,p.nama_perangkat,d.tahapan,d.kondisi,d.status,d.keterangan FROM detail_checklist d JOIN perangkat p ON p.id_perangkat=d.id_perangkat WHERE d.id_checklist IN ($in) ORDER BY FIELD(d.tahapan,'SEBELUM','SELAMA','SESUDAH','5R'),p.nama_perangkat");
    $st->execute($ids);
    foreach ($st->fetchAll() as $r) { $detail[$r['id_checklist']][] = $r; }
}
require __DIR__.'/includes/header.php';
?>
<div class="welcome-row"><div><span class="eyebrow">RIWAYAT SAYA</span><h1>Riwayat Checklist</h1><p>Semua checklist yang diisi oleh akunmu (<?= e($_SESSION['user']['nama']) ?>), lengkap dengan detail tiap tahapan.</p></div><a class="btn btn-primary" href="dashboard.php#checklist">+ Isi Checklist</a></div>

<section class="panel form-panel"><form method="get"><div class="form-grid four-cols">
  <div class="form-field"><label>Dari tanggal</label><input class="form-control" type="date" name="dari" value="<?= e($dari) ?>"></div>
  <div class="form-field"><label>Sampai tanggal</label><input class="form-control" type="date" name="sampai" value="<?= e($sampai) ?>"></div>
  <div class="form-field"><label>&nbsp;</label><button class="btn btn-primary btn-full">Filter</button></div>
  <div class="form-field"><label>&nbsp;</label><a class="btn btn-ghost btn-full" href="riwayat.php">Reset</a></div>
</div></form></section>

<?php foreach ($sesi as $c): $rows = $detail[$c['id_checklist']] ?? []; $ya = count(array_filter($rows, fn($r) => $r['status'] === 'Ya')); ?>
<section class="panel table-panel"><div class="panel-heading"><div><h2><?= e(date('d M Y', strtotime($c['tanggal']))) ?> · <?= e(substr($c['jam'], 0, 5)) ?></h2><p><?= count($rows) ?> pemeriksaan, <?= $ya ?> berstatus Ya.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Tahapan</th><th>Perangkat</th><th>Status</th><th>Kondisi</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($lbl[$r['tahapan']] ?? $r['tahapan']) ?></b></td><td><?= e($r['nama_perangkat']) ?></td><td><span class="status-pill"><?= e($r['status']) ?></span></td><td><?= e($r['kondisi'] ?: '-') ?></td><td><?= e($r['keterangan'] ?: '-') ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="table-empty">Tidak ada detail.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endforeach; ?>
<?php if (!$sesi): ?><section class="panel"><div class="empty-state"><span>▥</span><b>Belum ada riwayat</b><p>Checklist yang kamu isi akan muncul di sini.</p><a class="text-link" href="dashboard.php#checklist">Isi checklist →</a></div></section><?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>
