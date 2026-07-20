# Memperbarui Repository GitHub

Gunakan panduan ini setiap kali ada perubahan pada SIGAP Sumsel, App BHP, atau tools pendukung.

## Repository

| Proyek | Folder lokal | Repository |
| --- | --- | --- |
| SIGAP Sumsel | `D:\Laporan_Karo\laragon-portable\www\sigap-sumsel` | `Hiirooo/sigap-sumsel` |
| App BHP | `D:\Laporan_Karo\laragon-portable\www\app-bhp` | `Hiirooo/app-bhp` |
| Tools | `D:\Laporan_Karo\laragon-portable\www\tools` | `Hiirooo/tools-sigap-app-bhp` |

Semua repository harus tetap **private**.

## Alur Pembaruan

Buka PowerShell pada folder proyek yang akan diperbarui, lalu periksa perubahan:

```powershell
git status --short
git diff
```

Pastikan file berikut tidak masuk daftar perubahan yang akan diunggah:

- `.env` dan `config.json`
- Cookie, token, API key, password, dan private key
- Database, backup, log, cache, dan session
- `vendor`, `node_modules`, serta hasil build
- Dokumen, media upload, hasil scraping, dan data pengujian lokal

Stage perubahan yang aman dan periksa kembali:

```powershell
git add .
git status --short
git diff --cached
```

Buat commit dengan pesan yang menjelaskan perubahan:

```powershell
git commit -m "Jelaskan perubahan secara singkat"
```

Unggah commit ke GitHub:

```powershell
git push origin main
```

Verifikasi hasilnya:

```powershell
git status --short --branch
git log --oneline -3
```

Status yang benar setelah push adalah `main...origin/main` tanpa file perubahan yang belum di-commit.

## Jika Git Tidak Ditemukan

Gunakan lokasi Git secara eksplisit:

```powershell
& "C:\Program Files\Git\cmd\git.exe" status
& "C:\Program Files\Git\cmd\git.exe" add .
& "C:\Program Files\Git\cmd\git.exe" commit -m "Jelaskan perubahan secara singkat"
& "C:\Program Files\Git\cmd\git.exe" push origin main
```

## Aturan Keamanan

Jangan gunakan `git add -f` untuk memaksa file yang di-ignore. Jika file sensitif terlanjur staged, keluarkan sebelum commit:

```powershell
git restore --staged path\ke\file
```

Jika file sensitif sudah terlanjur dipush, segera ganti kredensial terkait dan bersihkan riwayat repository sebelum melanjutkan pekerjaan.
