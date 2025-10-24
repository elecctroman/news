<?php
namespace App\Services;

class SecurityScanner
{
    public function __construct(private string $rootPath)
    {
    }

    /**
     * @return array<int,array{name:string,status:string,message:string}>
     */
    public function run(): array
    {
        $results = [];
        $envFile = $this->rootPath . '/config/.env.php';
        if (is_file($envFile)) {
            $perms = substr(sprintf('%o', fileperms($envFile)), -4);
            $results[] = [
                'name' => '.env.php izinleri',
                'status' => ((int) $perms <= 640) ? 'pass' : 'warn',
                'message' => 'Mevcut izin: ' . $perms,
            ];
        } else {
            $results[] = [
                'name' => '.env.php bulunamadı',
                'status' => 'fail',
                'message' => 'Kurulum tamamlanmamış veya dosya taşınmış olabilir.',
            ];
        }

        $htaccess = $this->rootPath . '/public/.htaccess';
        if (is_file($htaccess)) {
            $contents = file_get_contents($htaccess) ?: '';
            $results[] = [
                'name' => 'Directory listing kontrolü',
                'status' => str_contains($contents, 'Options -Indexes') ? 'pass' : 'warn',
                'message' => 'public/.htaccess içerisinde Options -Indexes bulunmalıdır.',
            ];
        } else {
            $results[] = [
                'name' => '.htaccess bulunamadı',
                'status' => 'fail',
                'message' => 'Apache yapılandırması dizin listelemeye açık olabilir.',
            ];
        }

        $uploads = realpath($this->rootPath . '/storage/uploads');
        $public = realpath($this->rootPath . '/public');
        if ($uploads && $public) {
            $results[] = [
                'name' => 'Yüklemeler public dışı',
                'status' => str_starts_with($uploads, $public) ? 'fail' : 'pass',
                'message' => 'Upload dizini public kökünün dışında olmalıdır.',
            ];
        }

        $csrfFile = $this->rootPath . '/app/Core/Csrf.php';
        $csrfContent = is_file($csrfFile) ? file_get_contents($csrfFile) : '';
        $results[] = [
            'name' => 'CSRF token üretimi',
            'status' => str_contains($csrfContent, 'random_bytes') ? 'pass' : 'warn',
            'message' => 'CSRF yardımcı sınıfı kriptografik rastgelelik kullanmalıdır.',
        ];

        $limiterFile = $this->rootPath . '/app/Core/RateLimiter.php';
        $results[] = [
            'name' => 'Rate limit kontrolü',
            'status' => is_file($limiterFile) ? 'pass' : 'fail',
            'message' => is_file($limiterFile) ? 'Rate limiter bulundu.' : 'Rate limiter eksik.',
        ];

        return $results;
    }
}
