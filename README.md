# PHP Theme Engine

Basit, statik ve PSR-4 uyumlu bir **PHP Tema Motoru** 🎨  
Layout, block, partial, asset ve opsiyonel cache desteği ile minimal projelerinizde hızlıca kullanılabilir.

## 🚀 Özellikler
- ✅ **Statik kullanım** (nesne oluşturmanıza gerek yok)
- ✅ **Layout (extend)** desteği
- ✅ **Block (start / end / yield)** sistemi
- ✅ **Partial (kısmi şablon) render etme**
- ✅ **Asset helper** (CSS/JS/IMG yollarını otomatik üretir)
- ✅ **HTML escape helper**
- ✅ **Basit dosya tabanlı cache** (opsiyonel)
- ✅ **PSR-4 ve Composer uyumlu**

---

## 📦 Kurulum

### 1. Composer ile ekle
```bash
composer require senin-adin/php-theme-engine
```

### 2. Manuel (Composer olmadan)
`src/ThemeEngine.php` dosyasını projenize dahil edin ve `require` ile çağırın.

---

## 📂 Proje Yapısı (Örnek)

```
php-theme-engine/
│
├─ composer.json
├─ src/
│   └─ ThemeEngine.php
├─ index.php
└─ themes/
    └─ my-theme/
        ├─ layouts/
        │   └─ main.php
        ├─ pages/
        │   └─ home.php
        └─ partials/
            └─ header.php
```

---

## 🧩 Kullanım Örneği

### `index.php`
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use ThemeEngine\ThemeEngine;

// Tema motorunu başlat
ThemeEngine::init(__DIR__ . '/themes', 'my-theme', [
    'cache' => false, // Cache kullanmak için true yapabilirsiniz
]);

// Sayfayı render et
echo ThemeEngine::render('pages.home', [
    'title' => 'Ana Sayfa',
    'message' => 'Merhaba Dünya!'
]);
```

---

### `themes/my-theme/layouts/main.php`
```php
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= ThemeEngine::e($title ?? 'Başlık') ?></title>
    <link rel="stylesheet" href="<?= ThemeEngine::asset('assets/css/style.css') ?>">
</head>
<body>
    <!-- Header -->
    <?= ThemeEngine::partial('partials/header') ?>

    <!-- İçerik -->
    <main>
        <?= ThemeEngine::yield('content') ?>
    </main>

    <footer>
        <p>© 2025 - PHP Theme Engine</p>
    </footer>
</body>
</html>
```

---

### `themes/my-theme/partials/header.php`
```php
<header>
    <h1>Site Header</h1>
    <nav>
        <a href="/">Anasayfa</a>
        <a href="/about">Hakkında</a>
    </nav>
</header>
```

---

### `themes/my-theme/pages/home.php`
```php
<?php ThemeEngine::extend('layouts/main'); ?>

<?php ThemeEngine::start('content'); ?>
    <h2><?= ThemeEngine::e($title) ?></h2>
    <p><?= ThemeEngine::e($message) ?></p>
<?php ThemeEngine::end(); ?>
```

---

## 🛠 Diğer Fonksiyonlar

- **extend**: Bir layout belirtmek için  
  ```php
  ThemeEngine::extend('layouts/main');
  ```

- **start / end**: Block başlatma ve bitirme  
  ```php
  ThemeEngine::start('content');
  echo "Merhaba!";
  ThemeEngine::end();
  ```

- **yield**: Layout içinde block çağırma  
  ```php
  <?= ThemeEngine::yield('content') ?>
  ```

- **partial**: Başka bir template dahil etme  
  ```php
  <?= ThemeEngine::partial('partials/header') ?>
  ```

- **asset**: CSS/JS/IMG yolu üretme  
  ```php
  <script src="<?= ThemeEngine::asset('assets/js/app.js') ?>"></script>
  ```

- **e**: Güvenli HTML çıktısı (XSS önleme)  
  ```php
  <?= ThemeEngine::e($kullaniciAdi) ?>
  ```

---

## 📜 Lisans

MIT Lisansı ile yayınlanmıştır.  
Dilediğiniz gibi kullanabilir ve geliştirebilirsiniz.

---

## 👨‍💻 Katkıda Bulunma

Pull request ve issue açabilirsiniz.  
Yeni özellik önerilerinizi memnuniyetle değerlendiririm. 🚀
