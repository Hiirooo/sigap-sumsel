<?php

namespace App\Services;
use App\Models\Kliping;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleSentimentService
{
    public function analyzeUrl(string $url): array
    {
        $response = $this->fetchUrl($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Halaman berita tidak dapat dibaca.');
        }

        $article = $this->extractArticle($response->body(), $url);
        $analysis = $this->analyzeTextAndRelevance($article['title'].' '.$article['content']);

        return array_merge($article, $analysis);
    }

    private function analyzeTextAndRelevance(string $text): array
    {
        $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($text)));

        if (config('services.openai.key')) {
            $aiResult = $this->analyzeTextAndRelevanceWithAi($plainText);

            if ($aiResult) {
                return $aiResult;
            }
        }

        return array_merge(
            $this->analyzeTextWithRules($plainText),
            $this->checkRelevanceWithRules($plainText),
        );
    }

    private function analyzeTextAndRelevanceWithAi(string $text): ?array
    {
        $text = Str::limit($text, 8000, '');

        if ($text === '') {
            return null;
        }

        return $this->callCombinedAiModel(config('services.openai.model'), $text);
    }

    private function callCombinedAiModel(string $model, string $text): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withToken(config('services.openai.key'))
                ->acceptJson()
                ->post(rtrim(config('services.openai.base_url'), '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'max_completion_tokens' => 700,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah analis kliping Pemerintah Provinsi Sumatera Selatan. Lakukan dua tugas sekaligus: (1) klasifikasikan sentimen publik berita berbahasa Indonesia terhadap Gubernur Sumsel, Wakil Gubernur Sumsel, Sekda/Sekretaris Daerah Provinsi Sumsel, Pemprov Sumsel, Setda Provinsi Sumsel, atau kegiatan/kebijakan pimpinan Pemprov Sumsel; (2) nilai apakah berita berkaitan langsung dengan pimpinan tingkat PROVINSI tersebut. Jawab hanya JSON valid dengan field: sentimen (positif|netral|negatif), confidence (0-100), alasan_singkat, terkait_pimpinan (boolean), persentase_keterkaitan (0-100), tingkat_keterkaitan (tidak_terkait|rendah|sedang|tinggi), kata_kunci_keterkaitan. Sentimen positif jika pemberitaan memperkuat citra/kinerja/solusi pemerintah. Negatif jika menyorot masalah, kritik, kegagalan, konflik, kerugian, kriminalitas, antrean/keluhan publik, atau risiko reputasi. Netral jika hanya informatif/seremonial tanpa penilaian kuat. Skor keterkaitan 0-24 tidak terkait, 25-49 hanya sedikit/rendah, 50-74 cukup/sedang, 75-100 kuat/tinggi. Jangan menganggap terkait hanya karena lokasi Sumatera Selatan atau karena ada kata Sekda. Sekda/Pemkot/Pemkab/OPD kota atau kabupaten seperti Palembang, Prabumulih, Banyuasin, Ogan Ilir, OKI, OKU, Muara Enim, Lahat, Pagar Alam, Lubuklinggau, Musi Rawas, Empat Lawang, PALI, dan Muba adalah TIDAK TERKAIT jika tidak ada hubungan jelas dengan Pemprov Sumsel atau pimpinan provinsi.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('choices.0.message.content');
            $result = is_string($content) ? json_decode($content, true) : null;

            if (! is_array($result) || ! in_array($result['sentimen'] ?? null, ['positif', 'netral', 'negatif'], true)) {
                return null;
            }

            $score = max(0, min(100, (int) ($result['persentase_keterkaitan'] ?? 0)));
            if ($this->isLocalGovernmentOnly($text)) {
                $score = min($score, 25);
            }

            return [
                'sentimen' => $result['sentimen'],
                'confidence' => max(0, min(100, (int) ($result['confidence'] ?? 70))),
                'positive_score' => null,
                'negative_score' => null,
                'sentimen_metode' => 'ai',
                'sentimen_model' => $model,
                'alasan_singkat' => Str::limit((string) ($result['alasan_singkat'] ?? ''), 250, ''),
                'terkait_pimpinan' => $score >= 50 && (bool) ($result['terkait_pimpinan'] ?? true),
                'persentase_keterkaitan' => $score,
                'tingkat_keterkaitan' => $this->relevanceLevel($score, (string) ($result['tingkat_keterkaitan'] ?? '')),
                'kata_kunci_keterkaitan' => Str::limit((string) ($result['kata_kunci_keterkaitan'] ?? ''), 255, ''),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function analyzeText(string $text): array
    {
        if (config('services.openai.key')) {
            $aiResult = $this->analyzeTextWithAi($text);

            if ($aiResult) {
                return $aiResult;
            }
        }

        return $this->analyzeTextWithRules($text);
    }

    private function analyzeTextWithRules(string $text): array
    {
        $text = Str::lower(strip_tags($text));
        $positiveWords = [
            'apresiasi', 'baik', 'berhasil', 'berprestasi', 'berkualitas', 'cepat', 'dukung', 'dukungan',
            'efektif', 'efisien', 'inovasi', 'kolaborasi', 'lancar', 'maju', 'meningkat', 'optimal',
            'pelayanan', 'penghargaan', 'positif', 'prestasi', 'puas', 'sukses', 'terbaik', 'unggul',
        ];
        $negativeWords = [
            'banjir', 'bermasalah', 'buruk', 'gagal', 'hambatan', 'keluhan', 'kendala', 'konflik',
            'korupsi', 'krisis', 'kritik', 'lambat', 'macet', 'masalah', 'negatif', 'penolakan',
            'protes', 'rendah', 'rusak', 'sulit', 'terhambat', 'turun', 'viral',
        ];

        $positiveScore = $this->countWords($text, $positiveWords);
        $negativeScore = $this->countWords($text, $negativeWords);
        $difference = abs($positiveScore - $negativeScore);
        $total = max(1, $positiveScore + $negativeScore);

        $sentiment = 'netral';
        if ($difference >= 2) {
            $sentiment = $positiveScore > $negativeScore ? 'positif' : 'negatif';
        }

        return [
            'sentimen' => $sentiment,
            'confidence' => min(95, 50 + (int) round(($difference / $total) * 45)),
            'positive_score' => $positiveScore,
            'negative_score' => $negativeScore,
            'sentimen_metode' => 'rule_based',
        ];
    }

    private function analyzeTextWithAi(string $text): ?array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        $text = Str::limit($text, 8000, '');

        if ($text === '') {
            return null;
        }

        foreach (config('services.openai.models', [config('services.openai.model')]) as $model) {
            $result = $this->callAiModel($model, $text);

            if ($result) {
                return $result;
            }
        }

        return null;
    }

    private function callAiModel(string $model, string $text): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withToken(config('services.openai.key'))
                ->acceptJson()
                ->post(rtrim(config('services.openai.base_url'), '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'max_completion_tokens' => 512,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah analis media pemerintah daerah. Klasifikasikan sentimen publik berita berbahasa Indonesia terhadap Gubernur, Wakil Gubernur, Sekda/Sekretaris Daerah, atau Pemerintah Provinsi Sumatera Selatan. Jawab hanya JSON valid dengan field: sentimen (positif|netral|negatif), confidence (0-100), alasan_singkat. Positif jika pemberitaan memperkuat citra/kinerja/solusi pemerintah. Negatif jika menyorot masalah, kritik, kegagalan, konflik, kerugian, kriminalitas, antrean/keluhan publik, atau risiko reputasi. Netral jika hanya informatif/seremonial tanpa penilaian kuat.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('choices.0.message.content');
            $result = is_string($content) ? json_decode($content, true) : null;

            if (! is_array($result) || ! in_array($result['sentimen'] ?? null, ['positif', 'netral', 'negatif'], true)) {
                return null;
            }

            return [
                'sentimen' => $result['sentimen'],
                'confidence' => max(0, min(100, (int) ($result['confidence'] ?? 70))),
                'positive_score' => null,
                'negative_score' => null,
                'sentimen_metode' => 'ai',
                'sentimen_model' => $model,
                'alasan_singkat' => Str::limit((string) ($result['alasan_singkat'] ?? ''), 250, ''),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function checkRelevance(string $text): array
    {
        $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($text)));

        if (config('services.openai.key')) {
            $aiResult = $this->checkRelevanceWithAi($plainText);

            if ($aiResult) {
                return $aiResult;
            }
        }

        return $this->checkRelevanceWithRules($plainText);
    }

    private function checkRelevanceWithRules(string $text): array
    {
        $text = Str::lower($text);
        $keywords = [
            'gubernur sumsel',
            'gubernur sumatera selatan',
            'herman deru',
            'wakil gubernur sumsel cik ujang',
            'cik ujang',
            'wakil gubernur sumsel',
            'wakil gubernur sumatera selatan',
            'wagub sumsel',
            'sekda sumsel',
            'sekda provinsi sumatera selatan',
            'sekretaris daerah sumsel',
            'sekretaris daerah provinsi sumatera selatan',
            'pemprov sumsel',
            'pemerintah provinsi sumatera selatan',
            'setda provinsi sumatera selatan',
        ];

        $matched = collect($keywords)
            ->filter(fn (string $keyword) => str_contains($text, $keyword))
            ->values()
            ->all();
        $score = $this->isLocalGovernmentOnly($text) ? 0 : min(100, count($matched) * 25);

        return [
            'terkait_pimpinan' => $score >= 50,
            'persentase_keterkaitan' => $score,
            'tingkat_keterkaitan' => $this->relevanceLevel($score),
            'kata_kunci_keterkaitan' => implode(', ', $matched),
        ];
    }

    private function checkRelevanceWithAi(string $text): ?array
    {
        $text = Str::limit($text, 8000, '');

        if ($text === '') {
            return null;
        }

        foreach (config('services.openai.models', [config('services.openai.model')]) as $model) {
            $result = $this->callRelevanceAiModel($model, $text);

            if ($result) {
                return $result;
            }
        }

        return null;
    }

    private function callRelevanceAiModel(string $model, string $text): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withToken(config('services.openai.key'))
                ->acceptJson()
                ->post(rtrim(config('services.openai.base_url'), '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'max_completion_tokens' => 512,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah analis kliping Pemerintah Provinsi Sumatera Selatan. Nilai apakah berita berkaitan langsung dengan pimpinan tingkat PROVINSI: Gubernur Sumsel, Wakil Gubernur Sumsel, Sekda/Sekretaris Daerah Provinsi Sumsel, Pemprov Sumsel, Setda Provinsi Sumsel, atau kegiatan/kebijakan pimpinan Pemprov Sumsel. Jawab hanya JSON valid dengan field: terkait_pimpinan (boolean), persentase_keterkaitan (0-100), tingkat_keterkaitan (tidak_terkait|rendah|sedang|tinggi), kata_kunci_keterkaitan, alasan_singkat. Skor 0-24 tidak terkait, 25-49 hanya sedikit/rendah, 50-74 cukup/sedang, 75-100 kuat/tinggi. Jangan menganggap terkait hanya karena lokasi Sumatera Selatan atau karena ada kata Sekda. Sekda/Pemkot/Pemkab/OPD kota atau kabupaten seperti Palembang, Prabumulih, Banyuasin, Ogan Ilir, OKI, OKU, Muara Enim, Lahat, Pagar Alam, Lubuklinggau, Musi Rawas, Empat Lawang, PALI, dan Muba adalah TIDAK TERKAIT jika tidak ada hubungan jelas dengan Pemprov Sumsel atau pimpinan provinsi.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('choices.0.message.content');
            $result = is_string($content) ? json_decode($content, true) : null;

            if (! is_array($result)) {
                return null;
            }

            $score = max(0, min(100, (int) ($result['persentase_keterkaitan'] ?? 0)));
            if ($this->isLocalGovernmentOnly($text)) {
                $score = min($score, 25);
            }

            return [
                'terkait_pimpinan' => $score >= 50 && (bool) ($result['terkait_pimpinan'] ?? true),
                'persentase_keterkaitan' => $score,
                'tingkat_keterkaitan' => $this->relevanceLevel($score, (string) ($result['tingkat_keterkaitan'] ?? '')),
                'kata_kunci_keterkaitan' => Str::limit((string) ($result['kata_kunci_keterkaitan'] ?? ''), 255, ''),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function relevanceLevel(int $score, string $level = ''): string
    {
        if ($score < 50 && in_array($level, ['sedang', 'tinggi'], true)) {
            $level = '';
        }

        if (in_array($level, ['tidak_terkait', 'rendah', 'sedang', 'tinggi'], true)) {
            return $level;
        }

        return match (true) {
            $score >= 75 => 'tinggi',
            $score >= 50 => 'sedang',
            $score >= 25 => 'rendah',
            default => 'tidak_terkait',
        };
    }

    private function isLocalGovernmentOnly(string $text): bool
    {
        $text = Str::lower(strip_tags($text));
        $hasProvinceLeader = collect([
            'gubernur sumsel',
            'gubernur sumatera selatan',
            'herman deru',
            'wakil gubernur sumsel',
            'wakil gubernur sumatera selatan',
            'wagub sumsel',
            'cik ujang',
            'sekda sumsel',
            'sekda provinsi sumatera selatan',
            'sekretaris daerah sumsel',
            'sekretaris daerah provinsi sumatera selatan',
            'pemprov sumsel',
            'pemerintah provinsi sumatera selatan',
            'setda provinsi sumatera selatan',
        ])->contains(fn (string $keyword) => str_contains($text, $keyword));

        if ($hasProvinceLeader) {
            return false;
        }

        return (bool) preg_match('/\b(pemkot|pemerintah kota|pemkab|pemerintah kabupaten|sekda kota|sekda kabupaten|sekretaris daerah kota|sekretaris daerah kabupaten)\b/i', $text);
    }

    private function extractArticle(string $html, string $url): array
    {
        $document = new DOMDocument();
        @$document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $schemaArticle = $this->schemaArticle($xpath);

        $title = $this->metaContent($xpath, 'property', 'og:title')
            ?: $this->metaContent($xpath, 'name', 'twitter:title')
            ?: $this->schemaValue($schemaArticle, 'headline')
            ?: trim($document->getElementsByTagName('title')->item(0)?->textContent ?? '');

        $publishedAt = $this->metaContent($xpath, 'property', 'article:published_time')
            ?: $this->metaContent($xpath, 'name', 'publishdate')
            ?: $this->schemaValue($schemaArticle, 'datePublished')
            ?: $this->scriptValue($html, 'publish_date')
            ?: null;
        $imageUrl = $this->metaContent($xpath, 'property', 'og:image')
            ?: $this->metaContent($xpath, 'name', 'twitter:image')
            ?: $this->schemaImage($schemaArticle)
            ?: null;
        $media = $this->metaContent($xpath, 'property', 'og:site_name')
            ?: parse_url($url, PHP_URL_HOST)
            ?: 'Media Online';

        $content = $this->schemaValue($schemaArticle, 'articleBody') ?: '';
        if ($content === '') {
            $paragraphs = $this->articleParagraphs($xpath);

            $content = collect(iterator_to_array($paragraphs))
                ->map(fn (DOMElement $node) => $this->cleanParagraph($node->textContent))
                ->filter(fn (?string $text) => $text && strlen($text) > 50)
                ->take(20)
                ->implode("\n\n");
        }

        if ($content === '') {
            throw new \RuntimeException('Isi artikel tidak ditemukan.');
        }

        return [
            'title' => Str::limit($title ?: 'Berita Online', 250, ''),
            'media' => $media,
            'published_at' => $publishedAt ? date('Y-m-d', strtotime($publishedAt)) : now()->toDateString(),
            'content' => $content,
            'image_url' => $imageUrl ? $this->resolveUrl($imageUrl, $url) : null,
        ];
    }

    public function downloadImage(string $imageUrl): ?string
    {
        $response = $this->fetchUrl($imageUrl);

        if (! $response->successful()) {
            return null;
        }

        $contentType = Str::lower($response->header('Content-Type', ''));
        $extension = match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
            default => null,
        };

        if (! $extension) {
            return null;
        }

        $contents = $response->body();

        if ($contents === '' || strlen($contents) > 20 * 1024 * 1024) {
            return null;
        }

        $path = 'uploads/kliping/'.Str::uuid().'.'.$extension;

        if (! Storage::disk($this->storageDisk())->put($path, $contents)) {
            return null;
        }

        return $path;
    }

    public function recoverKlipingImage(Kliping $kliping): ?string
    {
        if (! $kliping->url) {
            return null;
        }

        try {
            $response = $this->fetchUrl($kliping->url);

            if (! $response->successful()) {
                return null;
            }

            $article = $this->extractArticle($response->body(), $kliping->url);
            $path = ! empty($article['image_url']) ? $this->downloadImage($article['image_url']) : null;

            if ($path) {
                $kliping->update(['file_path' => $path]);
            }

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    private function storageDisk(): string
    {
        $disk = (string) config('services.kliping.storage_disk', 'local');

        return in_array($disk, ['local', 'google-drive'], true) ? $disk : 'local';
    }

    private function resolveUrl(string $url, string $baseUrl): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:'.$url;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($baseUrl, PHP_URL_HOST);

        return $scheme.'://'.$host.'/'.ltrim($url, '/');
    }

    private function metaContent(DOMXPath $xpath, string $attribute, string $value): ?string
    {
        $nodes = $xpath->query("//meta[@{$attribute}='{$value}']/@content");

        return $nodes->length ? trim($nodes->item(0)->nodeValue) : null;
    }

    private function articleParagraphs(DOMXPath $xpath): \DOMNodeList
    {
        $queries = [
            '//*[contains(concat(" ", normalize-space(@class), " "), " txt-article ")]//p',
            '//*[@itemprop="articleBody"]//p',
            '//article//p',
            '//main//p',
            '//p',
        ];

        foreach ($queries as $query) {
            $paragraphs = $xpath->query($query);

            if ($paragraphs->length >= 3) {
                return $paragraphs;
            }
        }

        return $xpath->query('//p');
    }

    private function schemaArticle(DOMXPath $xpath): ?array
    {
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $data = json_decode(trim($node->textContent), true);

            foreach ($this->schemaItems($data) as $item) {
                $type = $item['@type'] ?? null;
                $types = is_array($type) ? $type : [$type];

                if (array_intersect($types, ['Article', 'NewsArticle', 'ReportageNewsArticle'])) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function schemaItems(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            return $data['@graph'];
        }

        return [$data];
    }

    private function schemaValue(?array $schema, string $key): ?string
    {
        $value = $schema[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function schemaImage(?array $schema): ?string
    {
        $image = $schema['image'] ?? null;

        if (is_string($image)) {
            return $image;
        }

        if (is_array($image)) {
            return $image['url'] ?? $image[0]['url'] ?? $image[0] ?? null;
        }

        return null;
    }

    private function scriptValue(string $html, string $key): ?string
    {
        if (preg_match("/'".preg_quote($key, '/')."'\s*:\s*'([^']+)'/", $html, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function cleanParagraph(string $text): ?string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return null;
        }

        $blockedPatterns = [
            '/^\s*baca\s+juga\s*:?/i',
            '/^\s*advertisement\s*$/i',
            '/^\s*iklan\s*$/i',
            '/^\s*scroll\s+to\s+continue/i',
            '/^\s*lanjutkan\s+membaca/i',
            '/^\s*simak\s+berita/i',
            '/^\s*artikel\s+terkait/i',
            '/^\s*rekomendasi\s*$/i',
            '/^\s*tag\s*:/i',
            '/^\s*editor\s*:/i',
            '/^\s*reporter\s*:/i',
            '/^\s*sumber\s*:/i',
            '/^\s*ikuti\s+kami/i',
            '/^\s*bagikan\s*:/i',
            '/^\s*share\s*:/i',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return null;
            }
        }

        $text = preg_replace('/\bBACA\s+JUGA\s*:[^\n.]+/i', '', $text);
        $text = preg_replace('/\bADVERTISEMENT\b|\bIKLAN\b/i', '', $text);

        return trim(preg_replace('/\s+/', ' ', $text)) ?: null;
    }

    private function browserHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ];
    }

    private function fetchUrl(string $url): \Illuminate\Http\Client\Response
    {
        try {
            return Http::timeout(15)
                ->withHeaders($this->browserHeaders())
                ->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            if (! str_contains($exception->getMessage(), 'cURL error 60')) {
                throw $exception;
            }

            return Http::timeout(15)
                ->withoutVerifying()
                ->withHeaders($this->browserHeaders())
                ->get($url);
        }
    }

    private function countWords(string $text, array $words): int
    {
        return collect($words)->sum(fn (string $word) => preg_match_all('/\b'.preg_quote($word, '/').'\b/u', $text));
    }
}
