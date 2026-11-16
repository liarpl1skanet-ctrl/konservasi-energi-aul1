# EnergiLestari — Website Konservasi Energi

Website PHP native + MySQL/MariaDB dengan desain responsif hijau-putih. Struktur database mengikuti file SQL yang diberikan, yaitu `konservasi_energi_aul` dengan tabel `users`, `siswa`, `perangkat`, `checklist`, dan `detail_checklist`.

## Peran & menu (terbaru)
- **Siswa & Guru**: menu hanya *Dashboard* (tempat mengisi checklist + riwayat milik sendiri) dan *Edukasi*. Checklist otomatis tercatat atas akun yang login — tidak perlu memilih siswa.
- **Admin**: Dashboard ringkasan seluruh sistem, Riwayat Checklist (semua pengguna, bisa dihapus), Perangkat, Data Siswa, Pengguna, Edukasi. Admin tidak mengisi checklist.
- Akun level Siswa dapat dihubungkan ke data siswa (kolom `users.nis`) lewat halaman Pengguna.
- Database berubah: `checklist.id_user` (baru) dan `users.nis` (baru). Jika database lama sudah terlanjur di-import, jalankan `migrasi_checklist_per_user.sql` sekali; jika import ulang `database.sql`, tidak perlu.

## Fitur yang sudah dibuat
- Landing page modern dengan statistik yang dibaca dari database.
- Login dan logout berbasis session; kompatibel dengan password teks biasa pada SQL lama serta password hash untuk akun baru.
- Dashboard statistik dan riwayat checklist.
- Buat checklist berisi siswa, tanggal/jam, perangkat, tahapan, kondisi, status, dan keterangan.
- Daftar perangkat dengan tambah/edit/hapus untuk Admin.
- Daftar siswa dengan tambah/perbarui/hapus untuk Admin.
- Pengelolaan akun (Admin) dengan password hash untuk akun baru.
- Halaman edukasi dan program hemat energi.
- Validasi form dasar, prepared statements, output escaping, dan token CSRF.

## Menjalankan dengan XAMPP
1. Ekstrak folder `energilestari` ke `C:\xampp\htdocs\energilestari`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin`.
4. Pilih menu Import dan impor file `database.sql`. File ini membuat/memilih database `konservasi_energi_aul`, lalu membuat tabel dan data contoh.
5. Jika username/password MySQL lokal berbeda, sesuaikan `config/database.php`.
6. Buka `http://localhost/energilestari`.

## Akun contoh dari SQL
- Admin: `admin` / `123`
- Guru: `guru` / `123`
- Siswa: `Siswa` / `123`

**Keamanan:** akun di atas berasal dari dump SQL contoh dan password-nya masih sederhana. Ganti atau buat akun baru dengan password kuat sebelum dipakai sungguhan. Jangan publikasikan website dengan akun contoh ini.

## Catatan kesesuaian database
- Tabel dan kolom inti mengikuti SQL sumber, tidak membutuhkan tabel baru.
- Data statistik dampak (kWh/CO₂) tidak dibuat-buat karena database sumber tidak memiliki kolom konsumsi listrik atau faktor emisi. Dashboard menampilkan statistik yang memang dapat dihitung dari tabel yang tersedia.
- Halaman edukasi dan program saat ini berisi konten statis. Untuk CRUD artikel/program, database perlu ditambah tabel khusus setelah disepakati.
- Pastikan pengaturan PHP mendukung PDO MySQL.
