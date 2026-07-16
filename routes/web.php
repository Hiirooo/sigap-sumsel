<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('secure-files/dokumentasi/{dokumentasi}/signed', [\App\Http\Controllers\SecureFileController::class, 'dokumentasi'])
    ->middleware('signed')
    ->name('secure-files.dokumentasi.signed');

Route::get('secure-files/dokumentasi/{dokumentasi}/thumbnail/signed', [\App\Http\Controllers\SecureFileController::class, 'dokumentasiThumbnail'])
    ->middleware('signed')
    ->name('secure-files.dokumentasi-thumbnail.signed');
Route::get('secure-files/dokumentasi-media/{media}/signed', [\App\Http\Controllers\SecureFileController::class, 'dokumentasiMedia'])
    ->middleware('signed')
    ->name('secure-files.dokumentasi-media.signed');
Route::get('secure-files/dokumentasi-media/{media}/thumbnail/signed', [\App\Http\Controllers\SecureFileController::class, 'dokumentasiMediaThumbnail'])
    ->middleware('signed')
    ->name('secure-files.dokumentasi-media-thumbnail.signed');

Route::get('secure-files/kliping/{kliping}/signed', [\App\Http\Controllers\SecureFileController::class, 'kliping'])
    ->middleware('signed')
    ->name('secure-files.kliping.signed');

Route::get('secure-files/rilis/{rilis}/image/signed', [\App\Http\Controllers\SecureFileController::class, 'rilisImage'])
    ->middleware('signed')
    ->name('secure-files.rilis-image.signed');

Route::get('secure-files/rilis/{rilis}/images/{index}/signed', [\App\Http\Controllers\SecureFileController::class, 'rilisSupportingImage'])
    ->middleware('signed')
    ->whereNumber('index')
    ->name('secure-files.rilis-supporting-image.signed');

Route::get('/dashboard', function () {
    $months = collect(range(5, 0))->map(function ($index) {
        $date = now()->subMonths($index);

        return [
            'key' => $date->format('Y-m'),
            'label' => $date->translatedFormat('M Y'),
        ];
    });

    $countByMonth = function (string $model, string $dateColumn) use ($months) {
        return $months->map(function ($month) use ($model, $dateColumn) {
            return [
                'label' => $month['label'],
                'value' => $model::whereYear($dateColumn, substr($month['key'], 0, 4))
                    ->whereMonth($dateColumn, substr($month['key'], 5, 2))
                    ->count(),
            ];
        });
    };

    $monthlyRilis = $countByMonth(\App\Models\RilisBerita::class, 'tanggal_rilis');
    $monthlyDokumentasi = $countByMonth(\App\Models\Dokumentasi::class, 'tanggal');
    $monthlyKliping = $countByMonth(\App\Models\Kliping::class, 'tanggal');

    $monthlyTrend = $months->map(function ($month, $index) use ($monthlyRilis, $monthlyDokumentasi, $monthlyKliping) {
        return [
            'label' => $month['label'],
            'rilis' => $monthlyRilis[$index]['value'],
            'dokumentasi' => $monthlyDokumentasi[$index]['value'],
            'kliping' => $monthlyKliping[$index]['value'],
            'total' => $monthlyRilis[$index]['value'] + $monthlyDokumentasi[$index]['value'] + $monthlyKliping[$index]['value'],
        ];
    });

    $rilisCount = \App\Models\RilisBerita::count();
    $dokumentasiCount = \App\Models\Dokumentasi::count();
    $arsipCount = \App\Models\ArsipStatis::count();
    $klipingCount = \App\Models\Kliping::count();
    $totalDocuments = $rilisCount + $dokumentasiCount + $arsipCount + $klipingCount;
    $verifiedDocs = \App\Models\Dokumentasi::where('status_verifikasi', 'terverifikasi')->count();
    $publishedRilis = \App\Models\RilisBerita::where('status', 'terpublikasi')->count();

    return Inertia::render('Dashboard', [
        'stats' => [
            'rilis_berita' => $rilisCount,
            'dokumentasi' => $dokumentasiCount,
            'arsip_statis' => $arsipCount,
            'kliping' => $klipingCount,
            'kategori' => \App\Models\KategoriKegiatan::count(),
            'total_documents' => $totalDocuments,
            'published_rilis' => $publishedRilis,
            'verified_docs' => $verifiedDocs,
            'verification_rate' => $dokumentasiCount > 0 ? round(($verifiedDocs / $dokumentasiCount) * 100) : 0,
            'publication_rate' => $rilisCount > 0 ? round(($publishedRilis / $rilisCount) * 100) : 0,
            'this_month_uploads' => \App\Models\RilisBerita::whereMonth('tanggal_rilis', now()->month)->whereYear('tanggal_rilis', now()->year)->count()
                + \App\Models\Dokumentasi::whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->count()
                + \App\Models\Kliping::whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->count(),
            'monthly_trend' => $monthlyTrend,
            'composition' => [
                ['label' => 'Rilis Berita', 'value' => $rilisCount, 'color' => '#0c2d5e'],
                ['label' => 'Dokumentasi', 'value' => $dokumentasiCount, 'color' => '#1d4ed8'],
                ['label' => 'Kliping', 'value' => $klipingCount, 'color' => '#d4af37'],
                ['label' => 'Arsip Statis', 'value' => $arsipCount, 'color' => '#64748b'],
            ],
            'sentiment' => [
                ['label' => 'Positif', 'value' => \App\Models\Kliping::where('sentimen', 'positif')->count(), 'color' => '#16a34a'],
                ['label' => 'Netral', 'value' => \App\Models\Kliping::where('sentimen', 'netral')->count(), 'color' => '#d4af37'],
                ['label' => 'Negatif', 'value' => \App\Models\Kliping::where('sentimen', 'negatif')->count(), 'color' => '#dc2626'],
            ],
            'verification' => [
                ['label' => 'Terverifikasi', 'value' => $verifiedDocs, 'color' => '#16a34a'],
                ['label' => 'Draft', 'value' => \App\Models\Dokumentasi::where('status_verifikasi', 'draft')->count(), 'color' => '#d4af37'],
            ],
            'digitalization' => [
                ['label' => 'Belum Digitalisasi', 'value' => \App\Models\Dokumentasi::where('status_digitalisasi', 'belum_didigitalisasi')->count(), 'color' => '#dc2626'],
                ['label' => 'Sudah Digitalisasi', 'value' => \App\Models\Dokumentasi::where('status_digitalisasi', 'sudah_didigitalisasi')->count(), 'color' => '#1d4ed8'],
                ['label' => 'Sudah Diarsipkan', 'value' => \App\Models\Dokumentasi::where('status_digitalisasi', 'sudah_diarsipkan')->count(), 'color' => '#16a34a'],
            ],
            'leaders' => \App\Models\Dokumentasi::selectRaw("COALESCE(NULLIF(pimpinan_terkait, ''), 'Tidak Diisi') as label, COUNT(*) as value")
                ->groupBy('label')
                ->orderByDesc('value')
                ->take(5)
                ->get(),
            'logs' => \App\Models\LogAktivitas::with('user')->latest()->take(8)->get(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('inventaris/cetak-pdf', [\App\Http\Controllers\InventarisController::class, 'cetakPdf'])->name('inventaris.cetak-pdf');
    Route::resource('inventaris', \App\Http\Controllers\InventarisController::class)->only(['index']);
    Route::get('secure-files/dokumentasi/{dokumentasi}', [\App\Http\Controllers\SecureFileController::class, 'dokumentasi'])->name('secure-files.dokumentasi');
    Route::get('secure-files/dokumentasi-media/{media}', [\App\Http\Controllers\SecureFileController::class, 'dokumentasiMedia'])->name('secure-files.dokumentasi-media');
    Route::get('secure-files/kliping/{kliping}', [\App\Http\Controllers\SecureFileController::class, 'kliping'])->name('secure-files.kliping');
    Route::get('secure-files/arsip-statis/{arsip}', [\App\Http\Controllers\SecureFileController::class, 'arsip'])->name('secure-files.arsip');
    Route::get('monev', [\App\Http\Controllers\MonevChecklistController::class, 'index'])->name('monev.index');
    Route::get('monev/cetak-pdf', [\App\Http\Controllers\MonevChecklistController::class, 'cetakPdf'])->name('monev.cetak-pdf');

    Route::middleware('role:admin,operator')->group(function () {
        Route::post('rilis-berita/sync-sumselprov', [\App\Http\Controllers\RilisBeritaController::class, 'syncSumselprov'])
            ->middleware('throttle:120,1')
            ->name('rilis-berita.sync-sumselprov');
        Route::post('rilis-berita/{rilisBerita}/toggle-status', [\App\Http\Controllers\RilisBeritaController::class, 'toggleStatus'])->name('rilis-berita.toggle-status');
        Route::resource('rilis-berita', \App\Http\Controllers\RilisBeritaController::class);
        Route::post('dokumentasi/{dokumentasi}/toggle-status', [\App\Http\Controllers\DokumentasiController::class, 'toggleStatus'])->name('dokumentasi.toggle-status');
        Route::resource('dokumentasi', \App\Http\Controllers\DokumentasiController::class);
        Route::post('kliping/detect-url', [\App\Http\Controllers\KlipingController::class, 'detectUrl'])->name('kliping.detect-url');
        Route::patch('kliping/{kliping}/toggle-status', [\App\Http\Controllers\KlipingController::class, 'toggleStatus'])->name('kliping.toggle-status');
        Route::resource('kliping', \App\Http\Controllers\KlipingController::class);
        Route::resource('arsip-statis', \App\Http\Controllers\ArsipStatisController::class);
        Route::get('monev/create', [\App\Http\Controllers\MonevChecklistController::class, 'create'])->name('monev.create');
        Route::post('monev', [\App\Http\Controllers\MonevChecklistController::class, 'store'])->name('monev.store');
        Route::get('monev/{monev}/edit', [\App\Http\Controllers\MonevChecklistController::class, 'edit'])->name('monev.edit');
        Route::put('monev/{monev}', [\App\Http\Controllers\MonevChecklistController::class, 'update'])->name('monev.update');
        Route::delete('monev/{monev}', [\App\Http\Controllers\MonevChecklistController::class, 'destroy'])->name('monev.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('kategori-kegiatan', \App\Http\Controllers\KategoriKegiatanController::class);
        Route::resource('settings/users', \App\Http\Controllers\UserSettingController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('settings.users');
    });
});

Route::prefix('auth/google')->name('google.auth.')->group(function () {
    Route::get('/', [\App\Http\Controllers\GoogleOAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [\App\Http\Controllers\GoogleOAuthController::class, 'callback'])->name('callback');
});

require __DIR__.'/auth.php';
