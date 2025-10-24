# Dijital E-PIN Platformu İskeleti

Bu depo, PHP 8.2, vanilla JavaScript ve CSS kullanarak güvenli bir dijital ürün mağazası kurmak isteyenler için başlangıç noktası sunar. İskelet; kurulum sihirbazı, kimlik doğrulama akışları, ürün/varyant yönetimi ve mobil uyumlu katalog arayüzleri içerir.

## Öne Çıkan Özellikler

- **Kurulum Sihirbazı:** `?install=1` ile erişilen sihirbaz, MySQL tablolarını oluşturur, `.env.php` dosyasını yazar, şifreleme anahtarı üretir ve örnek verileri (kategoriler, ürünler, E-PIN ve hesap stokları) ekler.
- **Kimlik ve Güvenlik:** PDO + prepared statements, `password_hash`, CSRF koruması, dosya tabanlı rate limit, e-posta doğrulama, parola sıfırlama ve TOTP tabanlı 2FA (kurtarma kodları dahil).
- **Yetkilendirme:** Roller (`super_admin`, `operator`, `customer`), rol/izin matrisi ve admin/front guard orta katmanları.
- **Ürün Yönetimi:** Ürün CRUD, varyant ekleme, AES-256-GCM ile şifrelenmiş E-PIN ve hesap içerikleri için CSV içe aktarma panelleri.
- **Sepet & Ödeme:** Oturum tabanlı sepet, kupon doğrulama, idempotent mock ödeme gateway'i ve otomatik teslimat servisi.
- **Destek & Biletler:** Siparişe bağlı/bağımsız bilet açma, çoklu ek dosya yükleme (MIME doğrulamalı), etiket ve öncelik yönetimi, uyuşmazlık/escalation durumları ve admin makro yanıt şablonları.
- **Raporlama & Bakım:** Tarih aralığı filtreli satış/gider grafikleri, CSV dışa aktarım, SQL yedeği alma, log rotasyonu, otomatik güvenlik taraması ve IP beyaz listeli bakım modu.
- **Katalog & SEO:** Kategori/etiket filtreleri, tam metin arama, duyarlı ürün kart ızgarası ve JSON-LD ürün şeması.
- **Arayüz:** Erişilebilir (klavye ile gezinilebilir) front ve admin düzenleri, modern kart tabanlı tasarım, responsive gridler.

## Kurulum Adımları

1. Depoyu web sunucusunun çalışma dizinine yerleştirin ve `public/` klasörünü web kök dizini olarak işaretleyin.
2. `storage/cache`, `storage/logs`, `storage/backups` klasörlerinin PHP tarafından yazılabilir olduğundan emin olun.
3. Tarayıcıdan `http://alanadiniz/index.php?install=1` adresini açın ve veritabanı, SMTP gönderen adresi ile yönetici bilgilerini girin.
4. Kurulum tamamlandığında `config/.env.php` oluşturulur, örnek roller atanır, `admin@example.com / Admin123!` süper yönetici hesabı ve örnek ürünler eklenir.
5. Ana sayfa (`/`) ve yönetim paneli (`/admin`) artık kullanılabilir.

> **Not:** `.env.php` dosyasında yer alan `ENCRYPTION_KEY` değerini güvenli saklayın; AES-256-GCM şifrelemeleri bu anahtar üzerinden yapılır.

## Klasör Ağacı

```
app/
  Controllers/
    Admin/
      DashboardController.php
      MaintenanceController.php
      OrderController.php
      ProductController.php
      ReportController.php
      TicketController.php
    Front/
      AccountController.php
      AuthController.php
      CartController.php
      CatalogController.php
      CheckoutController.php
      HomeController.php
      PaymentController.php
      ProductController.php
      TicketController.php
  Core/
    AuditLogger.php
    Auth.php
    Cache.php
    Cart.php
    Controller.php
    Crypto.php
    Csrf.php
    Logger.php
    Mailer.php
    RateLimiter.php
    Router.php
    Totp.php
    Validator.php
    View.php
  Middlewares/
    AdminGuard.php
    AuthGuard.php
  Models/
    Account.php
    Category.php
    Coupon.php
    Order.php
    OrderItemDelivery.php
    Product.php
    ProductKey.php
    Tag.php
    Setting.php
    Ticket.php
    TicketAttachment.php
    TicketMacro.php
    TicketMessage.php
    TicketTag.php
    Transaction.php
    UserAddress.php
  Services/
    DeliveryService.php
    PaymentGateway/{PaymentGatewayInterface.php,MockGateway.php,BankGateway.php,WalletGateway.php}
    PaymentService.php
    BackupService.php
    ReportService.php
    SecurityScanner.php
  Views/
    layouts/{front.php,admin.php}
    front/
      auth/{login.php,register.php,verify.php,request-reset.php,reset.php,twofactor.php}
      errors/404.php
      home.php
      catalog.php
      cart.php
      checkout.php
      product.php
      product-not-found.php
      account.php
      orders.php
      order-detail.php
      order-success.php
      payment-mock.php
      delivery-view.php
      security.php
      tickets/{create.php,index.php,show.php}
    admin/
      dashboard.php
      errors/404.php
      maintenance.php
      orders.php
      products.php
      ticket-show.php
      reports.php
      settings.php
      tickets.php
      users.php
config/
  config.php
  .env.example.php
public/
  index.php
  .htaccess
  maintenance.html
  assets/
    css/app.min.css
    js/app.min.js
routes/web.php
scripts/
  install.php
  schema.sql
  rotate_logs.php
  security_scan.php
storage/{cache,logs,backups,uploads/tickets}
tests/{test_csrf.php,test_rate_limiter.php,test_crypto.php,test_totp.php,test_cart.php,test_coupon.php,test_ticket.php,test_backup.php,test_security_scan.php}
```

## Örnek Veriler

Kurulum sonrası otomatik eklenen içerikler:

- Süper yönetici hesabı: **admin@example.com / Admin123!**
- Demo müşteri hesabı: **customer@example.com / Customer123!**
- Kategoriler: *Oyun E-PIN*, *Abonelik*, *Hesaplar*
- Etiketler: *Steam*, *PlayStation*, *Netflix*
- Ürünler: "Steam Cüzdan Kodu 50 TL" (E-PIN stoklu) ve "Netflix Premium 1 Ay" (şifreli hesap kayıtlı)
- Varsayılan roller ve `access_admin`, `manage_products`, `view_reports` gibi izinler
- Örnek destek bileti: müşteri mesajı + admin yanıtı ile destek ekranlarını denemeniz için hazır gelir

Bu veriler, panelde ürün ve stok akışlarının denenebilmesi için şifrelenmiş olarak saklanır.

## Çalıştırma

Geliştirme aşamasında PHP yerleşik sunucusu ile uygulamayı ayağa kaldırabilirsiniz:

```bash
php -S localhost:8000 -t public
```

Ardından `http://localhost:8000` adresinden müşteri arayüzünü, `http://localhost:8000/admin` adresinden yönetim panelini ziyaret edin.

## Testler

Çekirdek bileşenleri doğrulamak için küçük PHP testleri bulunur:

```bash
php tests/test_csrf.php
php tests/test_rate_limiter.php
php tests/test_crypto.php
php tests/test_totp.php
php tests/test_cart.php
php tests/test_coupon.php
php tests/test_ticket.php
php tests/test_backup.php
php tests/test_security_scan.php
```

Tüm betikler `OK` çıktısı üretmelidir. TOTP testi, aynı anda hem kod üretip hem doğruladığından sistem saati senkron olmalıdır. Sepet ve kupon testleri oturum/sanal veritabanı üzerinde çalışır.

## Güvenlik ve En İyi Uygulamalar

- CSRF tokeni tüm formlara otomatik enjekte edilir; kontrol sırasında `Csrf::validateToken()` kullanın.
- `App\Core\Auth` sınıfı email doğrulama, parola sıfırlama ve 2FA sürecini yönetir. Yönetici paneli `AdminGuard` ile korunur ve 2FA zorunlu hale getirilir.
- Şifreli alanlar (E-PIN ve hesap içerikleri) AES-256-GCM kullanılarak saklanır. Şifreleme anahtarını değiştirmeniz gerekiyorsa eski verileri yeniden şifrelemeden önce yedek almayı unutmayın.

## Geliştirme İpuçları

- Yeni rotalar için `routes/web.php` dosyasını düzenleyin; Router dinamik parametreleri `{param}` sözdizimi ile eşler.
- Modeller hafif PDO sarmalayıcılarıdır. Daha karmaşık sorgular için `app/Models/` altında yeni sınıflar oluşturun.
- İçe aktarma formları CSV satırlarını textarea üzerinden kabul eder; her satırda tek anahtar veya `email,password,note` formatında hesap bilgisi beklenir.

Bu iskelet, dijital ürün platformu için temel kimlik, stok ve katalog bileşenlerini sunar; ödeme, sipariş ve bildirim süreçleri için servislere yeni modüller ekleyebilirsiniz.
