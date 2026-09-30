# STRUKTUR BRANCH GIT & STANDAR COMMIT

Dokumen ini mengatur alur kerja Git (Git Flow), konvensi penamaan branch, dan pedoman commit message untuk repositori Ryokourent.

---

## 1. Struktur Cabang Utama (Branching Strategy)

* `main`
  * Branch produksi resmi (*production-ready*).
  * Hanya boleh diperbarui melalui Pull Request (PR) yang telah diuji dan disetujui dari branch `develop` atau `hotfix/*`.
* `develop`
  * Branch integrasi aktif seluruh fitur baru.
  * Setiap pengerjaan task fitur bermula dan berakhir (merge) di sini.

---

## 2. Format Penamaan Branch Fitur & Perbaikan

Format standar penamaan branch:
* `feature/id-deskripsi-singkat`
* `fix/id-deskripsi-masalah`
* `hotfix/deskripsi-darurat`

### Daftar Contoh Branch Proyek:
* `feature/cpt-motor`
* `feature/cpt-booking`
* `feature/booking-form`
* `feature/pricing`
* `feature/availability`
* `feature/whatsapp`
* `feature/admin-dashboard`
* `fix/overlapping-bookings`
* `fix/wa-url-encoding`

---

## 3. Standar Pesan Commit (Conventional Commits)

Format wajib satu baris:
```text
<type>(<scope>): <keterangan singkat dalam bahasa inggris atau indonesia>
```

Tipe commit yang diizinkan:
* `feat`: Fitur baru untuk pengguna/sistem
* `fix`: Perbaikan bug atau kesalahan logika
* `chore`: Konfigurasi build, tooling, struktur folder, atau dependency
* `docs`: Pembaruan atau penambahan dokumentasi
* `style`: Perubahan formatting kode tanpa memengaruhi logika
* `refactor`: Perubahan struktur kode tanpa menambah fitur atau mengubah bug
* `test`: Penambahan atau pembaruan berkas unit testing

### Contoh Pesan Commit Resmi:
* `chore: initialize plugin structure`
* `feat: add motor custom post type`
* `feat: add booking form`
* `fix: prevent overlapping bookings`
* `test: add booking availability tests`
* `docs: update installation guide`
* `feat(pricing): calculate daily and weekly rates`
* `fix(security): sanitize custom plate number array in metabox`
