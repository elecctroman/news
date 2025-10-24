# Dijital E-PIN Platformu İskeleti

Bu depo, PHP 8.2, vanilla JavaScript ve CSS kullanarak güvenli bir dijital ürün mağazası kurmak isteyenler için başlangıç noktası sunar. İskelet; kimlik doğrulama akışları, ürün/varyant yönetimi ve mobil uyumlu katalog arayüzleri içerir.

## Öne Çıkan Özellikler

- **Manuel Kurulum:** `config/config.php` dosyasında veritabanı ve şifreleme ayarlarını yapın, ardından `scripts/schema.sql` dosyasını çalıştırarak gerekli tabloları oluşturun.
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
3. `config/config.php` dosyasını açarak veritabanı DSN, kullanıcı adı/parola ve 32 baytlık şifreleme anahtarını girin. Anahtar üretmek için `php -r "echo 'base64:' . base64_encode(random_bytes(32));"` komutunu kullanabilirsiniz.
4. MySQL/MariaDB veritabanınızı oluşturun ve `scripts/schema.sql` dosyasını çalıştırarak tabloları kurun (`mysql -u root -p epin < scripts/schema.sql`).
5. En az bir yönetici hesabı eklemek için aşağıdaki "Örnek Veriler" bölümündeki SQL betiğini çalıştırabilir veya kendi kayıtlarınızı oluşturabilirsiniz (şifreler PHP'nin `password_hash()` çıktısı olmalıdır).
6. Ana sayfa (`/`) ve yönetim paneli (`/admin`) yapılandırma tamamlandığında kullanılabilir.

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
public/
  index.php
  .htaccess
  maintenance.html
  assets/
    css/app.min.css
    js/app.min.js
routes/web.php
scripts/
  schema.sql
  rotate_logs.php
  security_scan.php
storage/{cache,logs,backups,uploads/tickets}
tests/{test_csrf.php,test_rate_limiter.php,test_crypto.php,test_totp.php,test_cart.php,test_coupon.php,test_ticket.php,test_backup.php,test_security_scan.php}
```

## Örnek Veriler

Kurulumdan sonra demo ortamını hızlıca denemek için aşağıdaki SQL betiğini çalıştırabilirsiniz. Şifre alanları PHP'nin `password_hash()` fonksiyonu ile üretilmiştir.

```sql
INSERT INTO roles (id, name) VALUES (1, 'super_admin') ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO roles (id, name) VALUES (2, 'operator') ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO roles (id, name) VALUES (3, 'customer') ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO role_permissions (role_id, permission) VALUES
  (1, 'access_admin'),
  (1, 'manage_products'),
  (1, 'manage_orders'),
  (1, 'manage_users'),
  (1, 'view_reports')
ON DUPLICATE KEY UPDATE permission = VALUES(permission);

INSERT INTO users (id, email, password, role_id, name, email_verified_at)
VALUES (1, 'admin@example.com', '$2y$12$QvrRSHZn9o4KlxXraiXp3ORpgXLcFB0T1rxHselXiPONEqDG/00N6', 1, 'Süper Admin', NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO categories (id, name, slug)
VALUES
  (1, 'Oyun E-PIN', 'oyun-epin'),
  (2, 'Abonelik', 'abonelik')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (id, name, slug, short_description, description, base_price, currency)
VALUES
  (1, 'Steam Cüzdan Kodu 50 TL', 'steam-cuzdan-50', 'Anında teslim Steam kodu.', 'Dijital teslimat, stokta anahtar mevcut.', 50.00, 'TRY'),
  (2, 'Netflix Premium 1 Ay', 'netflix-premium-1-ay', 'Premium Netflix hesabı.', 'Tek seferlik görüntüleme linki ile teslim.', 89.90, 'TRY')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO product_variants (id, product_id, name, price_modifier, stock)
VALUES
  (1, 1, 'Global', 0.00, 10),
  (2, 2, 'TR', 0.00, 5)
ON DUPLICATE KEY UPDATE stock = VALUES(stock);
```

E-PIN ve hesap stoklarını CSV içe aktarma ekranından veya manuel olarak `product_keys` / `accounts` tablolarına AES-256-GCM ile şifrelenmiş biçimde ekleyebilirsiniz.

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
