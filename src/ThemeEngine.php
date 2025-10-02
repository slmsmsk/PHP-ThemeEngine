<?php
declare(strict_types=1);

/**
 * Class ThemeEngine
 *
 * PHP tabanlı projeler için basit bir tema motoru.
 * Bu sınıf tamamen statik çalışır; nesne oluşturulmadan kullanılabilir.
 *
 * Özellikler:
 * - Layout (extend) desteği
 * - Block (start/end ve yield) sistemi
 * - Partial (kısmi şablon) render etme
 * - Asset helper ile CSS/JS gibi dosyalar için URL üretme
 * - HTML escape helper
 * - Basit dosya tabanlı cache (opsiyonel)
 *
 * Kullanım Örneği:
 * -------------------------------------------------
 * ThemeEngine::init(__DIR__ . '/themes', 'my-theme', ['cache' => false]);
 * echo ThemeEngine::render('pages.home', ['title' => 'Ana Sayfa']);
 * -------------------------------------------------
 *
 * @author  
 * @license MIT
 */
class ThemeEngine
{
    /**
     * @var string Tema klasörlerinin bulunduğu kök dizin
     */
    protected static string $themesPath;

    /**
     * @var string Aktif tema adı
     */
    protected static string $activeTheme;

    /**
     * @var array<string,string> Template block içerikleri
     */
    protected static array $blocks = [];

    /**
     * @var array<int,string> Block stack (start/end için)
     */
    protected static array $stack = [];

    /**
     * @var string|null Layout dosyası (extend ile set edilir)
     */
    protected static ?string $layout = null;

    /**
     * @var array<string,mixed> Template parametreleri
     */
    protected static array $params = [];

    /**
     * @var bool Cache kullanılsın mı?
     */
    protected static bool $useCache = false;

    /**
     * @var string Cache klasörü yolu
     */
    protected static string $cachePath;

    /**
     * Tema motorunu başlatır.
     *
     * @param string $themesPath  Tema klasörlerinin kök yolu
     * @param string $activeTheme Aktif tema adı
     * @param array  $options     Ek ayarlar: ['cache' => bool, 'cache_path' => string]
     *
     * @return void
     */
    public static function init(string $themesPath, string $activeTheme, array $options = []): void
    {
        self::$themesPath = rtrim($themesPath, '/\\');
        self::$activeTheme = $activeTheme;
        self::$useCache = $options['cache'] ?? false;
        self::$cachePath = $options['cache_path'] ?? sys_get_temp_dir() . '/phptheme_cache';

        if (self::$useCache && !is_dir(self::$cachePath)) {
            @mkdir(self::$cachePath, 0755, true);
        }
    }

    /**
     * Bir template render eder.
     *
     * @param string               $template Template adı (örn: "pages.home")
     * @param array<string,mixed>  $params   Template parametreleri
     *
     * @return string Render edilmiş HTML çıktısı
     *
     * @throws \RuntimeException Template bulunamazsa
     */
    public static function render(string $template, array $params = []): string
    {
        self::$params = $params;
        self::$blocks = [];
        self::$stack = [];
        self::$layout = null;

        $content = self::renderFile(self::findTemplate($template), $params);

        if (self::$layout !== null) {
            $content = self::renderFile(
                self::findTemplate(self::$layout),
                array_merge($params, ['content' => $content])
            );
        }

        return $content;
    }

    /**
     * Partial (kısmi şablon) render eder.
     *
     * @param string               $partial Partial adı (örn: "partials/header")
     * @param array<string,mixed>  $params  Ek parametreler
     *
     * @return string Render edilmiş içerik
     */
    public static function partial(string $partial, array $params = []): string
    {
        return self::renderFile(
            self::findTemplate($partial),
            array_merge(self::$params, $params)
        );
    }

    /**
     * Template içinden bir layout (ana şablon) set eder.
     *
     * @param string $layout Layout adı (örn: "layouts/main")
     *
     * @return void
     */
    public static function extend(string $layout): void
    {
        self::$layout = $layout;
    }

    /**
     * Yeni bir block başlatır.
     * Layout içine eklenecek içerikler bu block içinde toplanır.
     *
     * @param string $name Block adı
     *
     * @return void
     */
    public static function start(string $name): void
    {
        self::$stack[] = $name;
        ob_start();
    }

    /**
     * Başlatılan block’u bitirir ve içeriği kaydeder.
     *
     * @return void
     */
    public static function end(): void
    {
        $content = ob_get_clean();
        $name = array_pop(self::$stack);

        if (!isset(self::$blocks[$name])) {
            self::$blocks[$name] = $content;
        } else {
            self::$blocks[$name] .= $content;
        }
    }

    /**
     * Layout içinde block içeriğini çağırır.
     *
     * @param string $name    Block adı
     * @param string $default Varsayılan içerik (block yoksa)
     *
     * @return string Block içeriği veya varsayılan
     */
    public static function yield(string $name, string $default = ''): string
    {
        return self::$blocks[$name] ?? $default;
    }

    /**
     * Asset (css/js/img) için URL üretir.
     *
     * @param string      $path    Dosya yolu (örn: "assets/css/app.css")
     * @param string|null $baseUrl Opsiyonel base url
     *
     * @return string Tam asset URL
     */
    public static function asset(string $path, ?string $baseUrl = null): string
    {
        $base = $baseUrl ?? self::detectBaseUrl();
        return rtrim($base, '/') . '/' . trim("themes/" . self::$activeTheme . "/$path", '/');
    }

    /**
     * HTML escape helper.
     * XSS saldırılarına karşı güvenli çıktı üretir.
     *
     * @param mixed $value Kaçırılacak değer
     *
     * @return string Güvenli HTML
     */
    public static function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Template dosyasını render eder.
     *
     * @param string              $file   Template dosyası
     * @param array<string,mixed> $params Parametreler
     *
     * @return string Render edilmiş içerik
     *
     * @throws \RuntimeException Dosya yoksa
     */
    protected static function renderFile(string $file, array $params): string
    {
        if (!file_exists($file)) {
            throw new RuntimeException("Template not found: {$file}");
        }

        if (self::$useCache) {
            $cacheFile = self::$cachePath . '/' . md5($file) . '.php';
            if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($file)) {
                copy($file, $cacheFile);
            }
            $file = $cacheFile;
        }

        extract($params, EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    /**
     * Template dosyasının yolunu bulur.
     *
     * @param string $name Template adı (örn: "pages.home" veya "partials/header")
     *
     * @return string Template dosya yolu
     *
     * @throws \RuntimeException Template bulunamazsa
     */
    protected static function findTemplate(string $name): string
    {
        $name = str_replace('.', '/', $name);
        $path = self::$themesPath . '/' . self::$activeTheme . '/' . $name . '.php';

        if (file_exists($path)) {
            return $path;
        }

        if (file_exists($name)) {
            return $name;
        }

        throw new RuntimeException("Template file not found: {$path}");
    }

    /**
     * Base URL tahmin eder.
     * Geliştirme amaçlıdır; production için elle base url vermek daha güvenilirdir.
     *
     * @return string Base URL
     */
    protected static function detectBaseUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir    = rtrim(dirname($script), '/\\');
        return "{$scheme}://{$host}{$dir}";
    }
}
