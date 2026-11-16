<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/functions.php';
require_login();
/* Guru & Siswa mengisi checklist langsung di Dashboard. Halaman ini = riwayat semua pengguna untuk Admin. */
if (!is_admin()) { header('Location: dashboard.php'); exit; }
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $st = $pdo->prepare('DELETE FROM checklist WHERE id_checklist=?');
    $st->execute([(int)($_POST['id'] ?? 0)]);
    flash('Checklist berhasil dihapus.');
    header('Location: checklist.php');
    exit;
}

$pageTitle = 'Riwayat Checklist';
$filter = $_GET['level'] ?? '';
$where = in_array($filter, ['Guru', 'Siswa'], true) ? 'WHERE u.level=?' : '';
$st = $pdo->prepare('SELECT c.id_checklist,c.tanggal,c.jam,COALESCE(u.nama,s.nama,\'-\') AS nama,u.level,s.kelas,COUNT(d.id_detail) jumlah,SUM(d.status=\'Ya\') ya,
    GROUP_CONCAT(DISTINCT p.nama_perangkat ORDER BY p.nama_perangkat SEPARATOR \', \') perangkat
    FROM checklist c LEFT JOIN users u ON u.id_user=c.id_user LEFT JOIN siswa s ON s.nis=c.nis
    LEFT JOIN detail_checklist d ON d.id_checklist=c.id_checklist LEFT JOIN perangkat p ON p.id_perangkat=d.id_perangkat '.$where.'
    GROUP BY c.id_checklist,c.tanggal,c.jam,u.nama,s.nama,u.level,s.kelas ORDER BY c.tanggal DESC,c.jam DESC');
$st->execute($where ? [$filter] : []);
$rows = $st->fetchAll();

require __DIR__.'/includes/header.php';
?>
<div class="welcome-row"><div><span class="eyebrow">PANTAU &amp; KELOLA</span><h1>Riwayat Checklist</h1><p>Checklist yang diisi guru dan siswa dari dashboard masing-masing.</p></div>
<div class="inline-actions"><a class="btn <?= $filter === '' ? 'btn-primary' : 'btn-ghost' ?>" href="checklist.php">Semua</a><a class="btn <?= $filter === 'Guru' ? 'btn-primary' : 'btn-ghost' ?>" href="checklist.php?level=Guru">Guru</a><a class="btn <?= $filter === 'Siswa' ? 'btn-primary' : 'btn-ghost' ?>" href="checklist.php?level=Siswa">Siswa</a></div></div>

<section class="panel table-panel"><div class="panel-heading"><div><h2>Semua checklist</h2><p><?= count($rows) ?> sesi checklist tercatat.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Pengguna</th><th>Peran</th><th>Kelas</th><th>Tanggal</th><th>Jam</th><th>Perangkat</th><th>Ya</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td><div class="table-person"><span class="avatar avatar-small"><?= e(strtoupper(substr($r['nama'], 0, 1))) ?></span><b><?= e($r['nama']) ?></b></div></td>
<td><span class="status-pill"><?= e($r['level'] ?? '-') ?></span></td><td><?= e($r['kelas'] ?? '-') ?></td>
<td><?= e(date('d M Y', strtotime($r['tanggal']))) ?></td><td><?= e(substr($r['jam'], 0, 5)) ?></td><td><?= e($r['perangkat'] ?? '-') ?></td><td><?= (int)$r['ya'] ?>/<?= (int)$r['jumlah'] ?></td>
<td><form method="post" onsubmit="return confirm('Hapus checklist ini beserta detailnya?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id_checklist'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="table-empty">Belum ada data checklist.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
