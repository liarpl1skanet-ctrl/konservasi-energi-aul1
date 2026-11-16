-- Migrasi untuk database konservasi_energi_aul yang SUDAH terlanjur di-import.
-- Jalankan sekali saja lewat phpMyAdmin > tab SQL (pilih database konservasi_energi_aul dulu).
-- Jika kamu import ulang database.sql yang baru, file ini TIDAK perlu dijalankan.

-- 1. Akun bisa dihubungkan ke data siswa (khusus level Siswa)
ALTER TABLE `users`
  ADD COLUMN `nis` int(10) DEFAULT NULL AFTER `level`,
  ADD KEY `idx_users_nis` (`nis`),
  ADD CONSTRAINT `fk_users_siswa` FOREIGN KEY (`nis`) REFERENCES `siswa` (`nis`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Checklist sekarang milik akun yang sedang login (id_user), nis jadi opsional
ALTER TABLE `checklist`
  MODIFY `nis` int(10) DEFAULT NULL,
  ADD COLUMN `id_user` int(11) DEFAULT NULL AFTER `id_checklist`,
  ADD KEY `idx_checklist_user` (`id_user`),
  ADD CONSTRAINT `fk_checklist_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 3. Hubungkan akun contoh "Siswa" (Aul) ke siswa NIS 1, lalu pindahkan checklist lama miliknya
UPDATE `users` SET `nis` = 1 WHERE `id_user` = 3 AND `level` = 'Siswa';
UPDATE `checklist` c JOIN `users` u ON u.`nis` = c.`nis` SET c.`id_user` = u.`id_user` WHERE c.`id_user` IS NULL;
