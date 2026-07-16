<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Scheduler dan cPanel

Sinkronisasi berita Sumselprov dijalankan oleh Laravel Scheduler. Frekuensi dan jumlah halaman diatur melalui `.env`:

```env
SUMSELPROV_SYNC_CRON="0 * * * *"
SUMSELPROV_SYNC_MAX_PAGES=5
RILIS_IMAGE_CONVERT_WEBP=true
RILIS_IMAGE_STORAGE_DISK=google-drive
RILIS_IMAGE_WEBP_QUALITY=82
RILIS_IMAGE_MAX_WIDTH=1600
```

Nilai bawaan `0 * * * *` berarti setiap jam. Semua gambar rilis, baik upload manual maupun sinkronisasi Sumselprov, diproses melalui konfigurasi `RILIS_IMAGE_*`. Jika `RILIS_IMAGE_CONVERT_WEBP=true`, gambar dikonversi menjadi WebP; jika `false`, format asli dipertahankan. `RILIS_IMAGE_STORAGE_DISK` hanya menerima `google-drive` atau `local`. Setelah mengubah konfigurasi, jalankan `php artisan config:clear`.

Pada cPanel, buat satu Cron Job yang berjalan setiap menit. Sesuaikan path PHP dan direktori project dengan akun hosting:

```cron
* * * * * /usr/local/bin/php /home/USERNAME/sigap-sumsel/artisan schedule:run >> /dev/null 2>&1
```

Beberapa cPanel menyediakan path PHP versi tertentu, misalnya `/opt/cpanel/ea-php83/root/usr/bin/php`. Periksa melalui menu **Select PHP Version** atau dokumentasi penyedia hosting. Cron Job cPanel hanya memicu scheduler; Laravel tetap menjalankan impor sesuai `SUMSELPROV_SYNC_CRON` dan mencegah proses tumpang tindih.

## Google Drive Storage

File SIGAP disimpan di Google Drive melalui konfigurasi berikut:

```env
FILESYSTEM_DISK=google-drive
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REFRESH_TOKEN=
GOOGLE_DRIVE_FOLDER_ID=
```

`GOOGLE_DRIVE_FOLDER_ID` adalah ID folder tujuan yang terlihat pada URL folder Google Drive. Di cPanel, isi seluruh nilai tersebut melalui `.env`, lalu jalankan:

```bash
php artisan config:clear
```

Jangan menyimpan client secret atau refresh token di repository. Folder Drive harus dapat diakses oleh akun Google yang memberikan refresh token.

Disk file dokumentasi dapat diatur terpisah dari disk global:

```env
DOKUMENTASI_STORAGE_DISK=local
```

Nilai yang didukung adalah `local` atau `google-drive`. File dokumentasi lama dapat dipindahkan mengikuti konfigurasi dengan `php artisan storage:migrate-dokumentasi`.

Gambar rilis yang tersimpan di Google Drive diproksikan melalui cache lokal agar halaman publik lebih cepat. Cache berita terbaru dapat dipanaskan setelah deployment dengan:

```bash
php artisan cache:prewarm-rilis-images --limit=12
```

Thumbnail video dokumentasi dibuat di browser menggunakan HTML5 Video dan Canvas, kemudian dikirim sebagai WebP bersama file video. Proses upload ini tidak membutuhkan FFmpeg atau akses shell di server cPanel. Jika browser gagal mengekstrak frame, pengguna dapat memilih thumbnail secara manual pada form yang sama.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
