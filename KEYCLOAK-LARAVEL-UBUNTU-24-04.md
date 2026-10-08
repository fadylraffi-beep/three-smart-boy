# Panduan Instalasi Keycloak dan Laravel pada Satu VM Ubuntu Server 24.04

Panduan ini memasang **Keycloak** dan aplikasi **Laravel** pada satu VM Ubuntu Server 24.04. Panduan disesuaikan dengan project ini:

- Laravel 10
- PHP 8.1 atau lebih baru
- MySQL untuk database Laravel
- `aacotroneo/laravel-saml2` versi 2.1
- Keycloak sebagai Identity Provider (IdP) melalui SAML 2.0
- Nginx sebagai web server Laravel
- Keycloak berjalan pada port `8080`
- Laravel berjalan pada port `80`

Contoh alamat yang digunakan:

| Komponen | Alamat |
|---|---|
| Laravel | `http://192.168.10.67` |
| Keycloak | `http://192.168.10.67:8080` |
| Realm | `laravel-app` |
| SAML client | `laravel-sp` |

Ganti `192.168.10.67` dengan IP atau domain VM yang sebenarnya. Jangan menyalin password, private key, atau sertifikat ke Git.

> **Catatan produksi:** gunakan HTTPS untuk Laravel dan Keycloak jika aplikasi diakses melalui jaringan selain jaringan internal. Semua URL SAML harus menggunakan skema dan hostname yang sama dengan URL yang digunakan browser.

---

## 1. Prasyarat VM

Login ke VM menggunakan user yang memiliki akses `sudo`:

```bash
ssh <user>@192.168.10.67
```

Atur hostname dan timezone jika diperlukan:

```bash
sudo hostnamectl set-hostname laravel-keycloak-vm
sudo timedatectl set-timezone Asia/Jakarta
```

Update Ubuntu:

```bash
sudo apt update
sudo apt upgrade -y
```

Install utilitas dasar:

```bash
sudo apt install -y ca-certificates curl unzip git nginx \
    software-properties-common ufw
```

Pastikan jam VM benar. SAML sensitif terhadap perbedaan waktu antara VM dan Keycloak:

```bash
timedatectl status
```

Aktifkan sinkronisasi waktu:

```bash
sudo timedatectl set-ntp true
```

---

## 2. Firewall VM

Jika VM memakai UFW, buka SSH, HTTP, dan HTTPS:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
```

Port Keycloak `8080` sebaiknya **tidak dibuka ke Internet**. Karena Laravel dan Keycloak berada di VM yang sama, Keycloak dapat diakses melalui `127.0.0.1:8080` oleh Nginx atau aplikasi, sedangkan browser diarahkan ke port `8080` hanya jika memang diperlukan untuk login. Jika Keycloak harus diakses dari komputer lain, batasi sumbernya:

```bash
sudo ufw allow from <IP-JARINGAN-INTERNAL> to any port 8080 proto tcp
```

---

## 3. Instalasi PHP dan ekstensi Laravel

Ubuntu Server 24.04 menyediakan PHP 8.3, yang kompatibel dengan constraint project `^8.1`.

```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd
```

Verifikasi:

```bash
php -v
php -m | grep -E 'bcmath|curl|dom|fileinfo|gd|intl|mbstring|mysqli|openssl|pdo|pdo_mysql|tokenizer|xml|zip'
```

Jika `php8.3-fpm` belum aktif:

```bash
sudo systemctl enable --now php8.3-fpm
sudo systemctl status php8.3-fpm
```

---

## 4. Instalasi MySQL untuk Laravel

Install MySQL:

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
sudo mysql_secure_installation
```

Buat database dan user khusus aplikasi. Jangan menggunakan user `root` untuk aplikasi production:

```bash
sudo mysql
```

Jalankan SQL berikut dan ganti password dengan password kuat:

```sql
CREATE DATABASE `three-smart-boy`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'three_smart_boy'@'localhost'
    IDENTIFIED BY '<PASSWORD-DATABASE-LARAVEL>';

GRANT ALL PRIVILEGES ON `three-smart-boy`.*
    TO 'three_smart_boy'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

---

## 5. Instalasi Java untuk Keycloak

Keycloak modern memerlukan Java. Gunakan OpenJDK 21:

```bash
sudo apt install -y openjdk-21-jre-headless
java -version
```

Pastikan output menunjukkan Java 21 atau versi yang didukung oleh versi Keycloak yang dipilih.

---

## 6. Instalasi Keycloak

Gunakan versi Keycloak yang sudah ditetapkan oleh tim. Jangan mengubah versi secara acak di production. Contoh berikut menggunakan variabel versi agar mudah diganti:

```bash
export KEYCLOAK_VERSION=<VERSI-KEYCLOAK>
cd /tmp
curl -fLO "https://github.com/keycloak/keycloak/releases/download/${KEYCLOAK_VERSION}/keycloak-${KEYCLOAK_VERSION}.tar.gz"
sudo tar -xzf "keycloak-${KEYCLOAK_VERSION}.tar.gz" -C /opt
sudo ln -sfn "/opt/keycloak-${KEYCLOAK_VERSION}" /opt/keycloak
sudo useradd --system --home-dir /opt/keycloak --shell /usr/sbin/nologin keycloak || true
sudo chown -R keycloak:keycloak "/opt/keycloak-${KEYCLOAK_VERSION}"
```

Buat administrator awal. Jalankan hanya saat membuat instalasi baru:

```bash
sudo -u keycloak /opt/keycloak/bin/kc.sh bootstrap-admin user \
    --username <KEYCLOAK_ADMIN_USER> \
    --password:env=KC_BOOTSTRAP_ADMIN_PASSWORD
```

Perintah di atas memerlukan environment variable:

```bash
export KC_BOOTSTRAP_ADMIN_PASSWORD='<PASSWORD-ADMIN-KEYCLOAK>'
```

Hapus variable password dari shell setelah selesai:

```bash
unset KC_BOOTSTRAP_ADMIN_PASSWORD
```

### 6.1 Mode sederhana untuk jaringan internal

Untuk lab atau jaringan internal, Keycloak dapat dijalankan dengan HTTP pada port `8080`:

```bash
sudo -u keycloak /opt/keycloak/bin/kc.sh start \
    --http-enabled=true \
    --http-port=8080 \
    --hostname-strict=false
```

Untuk production, gunakan database eksternal, hostname tetap, dan HTTPS. Jangan menggunakan `start-dev` untuk production.

### 6.2 Service systemd Keycloak

Buat file service:

```bash
sudo tee /etc/systemd/system/keycloak.service > /dev/null <<'EOF'
[Unit]
Description=Keycloak Identity Provider
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=keycloak
Group=keycloak
WorkingDirectory=/opt/keycloak
Environment=KC_BOOTSTRAP_ADMIN_USERNAME=<KEYCLOAK_ADMIN_USER>
Environment=KC_BOOTSTRAP_ADMIN_PASSWORD=<PASSWORD-ADMIN-KEYCLOAK>
ExecStart=/opt/keycloak/bin/kc.sh start --http-enabled=true --http-port=8080 --hostname-strict=false
Restart=on-failure
RestartSec=5
LimitNOFILE=102400

[Install]
WantedBy=multi-user.target
EOF
```

Batasi permission file karena berisi password administrator:

```bash
sudo chmod 600 /etc/systemd/system/keycloak.service
sudo systemctl daemon-reload
sudo systemctl enable --now keycloak
sudo systemctl status keycloak
```

Untuk production, lebih baik menyimpan secret melalui environment file yang permission-nya ketat atau secret manager, bukan menulis password langsung di unit service.

Tes Keycloak:

```bash
curl -I http://127.0.0.1:8080
```

Buka admin console dari browser:

```text
http://192.168.10.67:8080
```

---

## 7. Konfigurasi Realm Keycloak

1. Login ke **Keycloak Administration Console**.
2. Buat realm baru dengan nama:

   ```text
   laravel-app
   ```

3. Pilih realm `laravel-app`.
4. Buat user uji pada menu **Users**.
5. Isi email user dan pastikan email tidak kosong.
6. Set password pada tab **Credentials** dan matikan **Temporary** jika ingin password tidak dipaksa berubah.

---

## 8. Konfigurasi SAML Client Keycloak

Di realm `laravel-app`, buat client baru:

1. Buka **Clients**.
2. Klik **Create client**.
3. Pilih protocol:

   ```text
   SAML 2.0
   ```

4. Gunakan Client ID:

   ```text
   laravel-sp
   ```

5. Simpan.

Gunakan nilai berikut. Ganti IP dengan alamat Laravel yang sebenarnya:

| Pengaturan Keycloak | Nilai |
|---|---|
| Client ID / Entity ID | `http://192.168.10.67/saml2/keycloak/metadata` |
| Valid Redirect URIs / Assertion Consumer Service URL | `http://192.168.10.67/saml2/keycloak/acs` |
| ACS binding | `HTTP-POST` |
| Single Logout Service URL | `http://192.168.10.67/saml2/keycloak/sls` |
| SLO binding | `HTTP-Redirect` |
| NameID format | `persistent` |

Hal penting:

- `/acs` hanya untuk **callback login POST**.
- `/sls` hanya untuk **callback logout**.
- Jangan menggunakan `/saml2/keycloak/acs` sebagai URL logout.
- Jangan membuka `/acs` langsung dari browser dengan GET.
- Jangan mengarahkan `RelayState` ke `/acs`.

### 8.1 Attribute mappers

Tambahkan SAML mapper agar Laravel menerima identitas user:

| SAML attribute | Source user property | Friendly name |
|---|---|---|
| `email` | Email | `email` |
| `name` | Full name | `name` |
| `given_name` | First name | `given_name` |
| `family_name` | Last name | `family_name` |
| `preferred_username` | Username | `preferred_username` |

Pastikan user Keycloak memiliki email dan nama. Jika atribut `email` tidak dikirim, aplikasi menggunakan fallback `<NameID>@sso.local`. Fallback hanya untuk kondisi darurat; konfigurasi email mapper tetap wajib.

### 8.2 Sertifikat IdP

Ambil sertifikat publik SAML dari konfigurasi atau metadata Keycloak. Sertifikat tersebut akan dimasukkan ke `SAML2_IDP_x509` pada `.env` Laravel. Jangan memasukkan private key Keycloak ke Laravel.

---

## 9. Deploy source code Laravel

Buat directory aplikasi:

```bash
sudo mkdir -p /var/www/three-smart-boy
sudo chown -R "$USER":www-data /var/www/three-smart-boy
cd /var/www/three-smart-boy
git clone <URL-REPOSITORY> .
```

Jika directory sudah ada:

```bash
cd /var/www/three-smart-boy
git pull origin main
```

Install Composer:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

Install dependency production:

```bash
cd /var/www/three-smart-boy
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
```

Pastikan `composer.lock` ikut di-pull. Jangan menjalankan `composer update` saat deployment normal.

---

## 10. Konfigurasi `.env` Laravel

Buat `.env` dari template jika belum ada:

```bash
cd /var/www/three-smart-boy
cp .env.example .env
php artisan key:generate
```

Jangan menjalankan `key:generate` jika aplikasi sudah pernah berjalan dan `.env` sudah memiliki `APP_KEY`. Mengganti `APP_KEY` akan membuat cookie/session lama tidak dapat dibaca.

Edit `.env`:

```bash
nano /var/www/three-smart-boy/.env
```

Contoh konfigurasi minimal:

```dotenv
APP_NAME="Three Smart Boy"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.10.67

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=three-smart-boy
DB_USERNAME=three_smart_boy
DB_PASSWORD=<PASSWORD-DATABASE-LARAVEL>

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

SAML2_RELAY_STATE_URL=http://192.168.10.67/dashboard
SAML2_IDP_ENTITYID=http://192.168.10.67:8080/realms/laravel-app
SAML2_IDP_SSO_URL=http://192.168.10.67:8080/realms/laravel-app/protocol/saml
SAML2_IDP_SLO_URL=http://192.168.10.67:8080/realms/laravel-app/protocol/saml
SAML2_IDP_x509="<SERTIFIKAT-PUBLIK-KEYCLOAK>"
```

Jika menggunakan domain HTTPS, semua URL di atas harus disesuaikan:

```dotenv
APP_URL=https://app.example.com
SAML2_RELAY_STATE_URL=https://app.example.com/dashboard
```

Untuk konfigurasi Keycloak yang tetap menggunakan IP dan port `8080`, `SAML2_IDP_*` dapat tetap menunjuk ke:

```dotenv
SAML2_IDP_ENTITYID=http://192.168.10.67:8080/realms/laravel-app
SAML2_IDP_SSO_URL=http://192.168.10.67:8080/realms/laravel-app/protocol/saml
SAML2_IDP_SLO_URL=http://192.168.10.67:8080/realms/laravel-app/protocol/saml
```

Jangan commit `.env`:

```bash
chmod 600 /var/www/three-smart-boy/.env
```

---

## 11. Migrasi database dan permission Laravel

Jalankan migrasi:

```bash
cd /var/www/three-smart-boy
php artisan migrate --force
```

Atur ownership dan permission:

```bash
sudo chown -R www-data:www-data /var/www/three-smart-boy
sudo find /var/www/three-smart-boy -type f -exec chmod 644 {} \;
sudo find /var/www/three-smart-boy -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/three-smart-boy/storage
sudo chmod -R 775 /var/www/three-smart-boy/bootstrap/cache
sudo chmod 600 /var/www/three-smart-boy/.env
```

Driver session project menggunakan file. Karena itu `storage/framework/sessions` harus dapat ditulis:

```bash
sudo mkdir -p /var/www/three-smart-boy/storage/framework/sessions
sudo chown -R www-data:www-data /var/www/three-smart-boy/storage
```

---

## 12. Build asset frontend

Jika repository sudah menyimpan hasil build asset, langkah ini dapat dilewati. Jika belum, install Node.js dan npm:

```bash
sudo apt install -y nodejs npm
cd /var/www/three-smart-boy
npm ci
npm run build
```

Pastikan directory `public/build` tersedia setelah build.

---

## 13. Konfigurasi Nginx Laravel

Buat virtual host:

```bash
sudo nano /etc/nginx/sites-available/three-smart-boy
```

Isi:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name 192.168.10.67;
    root /var/www/three-smart-boy/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan site:

```bash
sudo ln -sfn /etc/nginx/sites-available/three-smart-boy \
    /etc/nginx/sites-enabled/three-smart-boy
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

Jika memakai domain, ganti `server_name` dengan domain tersebut. Jangan membuat root Nginx menunjuk ke root repository; Laravel harus menunjuk ke directory `public`.

---

## 14. Cache Laravel setelah konfigurasi

Jalankan setelah `.env` dan file konfigurasi sudah benar:

```bash
cd /var/www/three-smart-boy
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verifikasi route:

```bash
php artisan route:list | grep -E 'login|logout|dashboard|saml2'
```

Route yang wajib tersedia:

```text
GET|HEAD  login
GET|HEAD  dashboard
GET|HEAD  saml2/{idpName}/login
GET|HEAD  saml2/{idpName}/logout
POST      saml2/{idpName}/acs
GET|HEAD  saml2/{idpName}/sls
GET|HEAD  saml2/{idpName}/metadata
```

---

## 15. Verifikasi metadata SAML

Buka:

```text
http://192.168.10.67/saml2/keycloak/metadata
```

Metadata harus memiliki endpoint berikut:

```text
Assertion Consumer Service:
http://192.168.10.67/saml2/keycloak/acs

Single Logout Service:
http://192.168.10.67/saml2/keycloak/sls
```

Jika metadata masih menampilkan URL lama, jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
sudo systemctl reload nginx
```

---

## 16. Urutan pengujian end-to-end

Lakukan pengujian dengan browser yang cookie lamanya sudah dibersihkan:

1. Buka `http://192.168.10.67/`.
2. Pastikan halaman home dapat dibuka tanpa login.
3. Pastikan CRUD blog tetap dapat digunakan sesuai kebutuhan aplikasi.
4. Buka `http://192.168.10.67/dashboard`.
5. Laravel mengarahkan user ke `/login`, lalu ke Keycloak.
6. Masukkan username dan password Keycloak.
7. Keycloak mengirim response **POST** ke:

   ```text
   /saml2/keycloak/acs
   ```

8. Laravel membuat atau memperbarui user lokal.
9. Laravel mengarahkan user ke `/dashboard`.
10. Dashboard menampilkan nama dan email dari session Laravel.
11. Klik Logout.
12. Logout harus menggunakan:

   ```text
   /saml2/keycloak/logout
   ```

13. Callback logout harus kembali ke:

   ```text
   /saml2/keycloak/sls
   ```

Jangan membuka `/saml2/keycloak/acs` langsung dengan GET. Endpoint tersebut memang hanya mendukung POST.

---

## 17. Troubleshooting

### `The GET method is not supported for route saml2/keycloak/acs`

Penyebabnya browser atau Keycloak mengakses ACS menggunakan GET. Perbaiki:

- ACS URL tetap `/saml2/keycloak/acs`.
- Binding response login harus `HTTP-POST`.
- Jangan memakai ACS sebagai URL logout.
- SLO URL harus `/saml2/keycloak/sls`.

### Redirect login selalu kembali ke home

Periksa:

```php
'routesMiddleware' => ['web'],
'loginRoute' => '/dashboard',
```

Lalu jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

Pastikan session file dapat ditulis oleh `www-data`.

### `Duplicate entry '<nilai>@sso.local'`

Ini berarti Keycloak tidak mengirim atribut `email`, sehingga aplikasi menggunakan fallback. Perbaiki email mapper di Keycloak dan pastikan `NameID` selalu tersedia serta stabil. Periksa user yang sudah terlanjur dibuat:

```sql
SELECT id, keycloak_id, name, email
FROM users
WHERE email LIKE '%@sso.local';
```

### Route `saml2_login` atau `saml2_logout` tidak ditemukan

Jalankan:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan route:list --path=saml2
```

Pastikan file `config/saml2_settings.php` berisi:

```php
'idpNames' => ['keycloak'],
'useRoutes' => true,
```

dan file berikut tersedia:

```text
config/saml2/keycloak_idp_settings.php
```

### Error SAML signature, certificate, atau issuer

Periksa:

- `SAML2_IDP_ENTITYID` sama dengan Entity ID Keycloak.
- `SAML2_IDP_x509` adalah sertifikat publik Keycloak yang benar.
- Tidak ada spasi/baris baru yang salah pada nilai sertifikat.
- Waktu VM tersinkronisasi.
- ACS dan SLO URL sama persis antara Keycloak dan metadata Laravel.

### Error session atau dashboard selalu meminta login

Periksa:

```bash
sudo ls -ld /var/www/three-smart-boy/storage/framework/sessions
sudo chown -R www-data:www-data /var/www/three-smart-boy/storage
```

Periksa juga `.env`:

```dotenv
APP_URL=http://192.168.10.67
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=
```

Jika menggunakan HTTPS, gunakan:

```dotenv
APP_URL=https://app.example.com
SESSION_SECURE_COOKIE=true
```

### Lihat log Laravel dan Nginx

```bash
sudo tail -f /var/www/three-smart-boy/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
sudo journalctl -u php8.3-fpm -f
sudo journalctl -u keycloak -f
```

Jangan membagikan isi log yang memuat `SAMLResponse`, cookie, token, password, private key, atau sertifikat private.

---

## 18. Checklist akhir

- [ ] Ubuntu 24.04 sudah diperbarui.
- [ ] Java tersedia dan kompatibel dengan Keycloak.
- [ ] Keycloak aktif sebagai service systemd.
- [ ] Realm `laravel-app` tersedia.
- [ ] SAML client `laravel-sp` tersedia.
- [ ] ACS Keycloak menunjuk ke `/saml2/keycloak/acs`.
- [ ] ACS menggunakan HTTP-POST.
- [ ] SLO Keycloak menunjuk ke `/saml2/keycloak/sls`.
- [ ] `config/saml2_settings.php` menggunakan `idpNames => ['keycloak']`.
- [ ] Route SAML menggunakan middleware `web`.
- [ ] `saml2/*/acs` dikecualikan dari CSRF.
- [ ] `.env` berisi `APP_URL` yang dapat diakses browser.
- [ ] `SAML2_IDP_ENTITYID`, `SAML2_IDP_SSO_URL`, dan `SAML2_IDP_SLO_URL` cocok dengan Keycloak.
- [ ] `SAML2_IDP_x509` berisi sertifikat publik Keycloak.
- [ ] `storage` dan `bootstrap/cache` dapat ditulis `www-data`.
- [ ] `composer install --no-dev` selesai.
- [ ] `php artisan optimize:clear` sudah dijalankan setelah perubahan `.env`.
- [ ] `php artisan route:list --path=saml2` menampilkan lima route SAML.
- [ ] Login berhasil dan mengarah ke dashboard.
- [ ] Nama user tampil dari session Laravel.
- [ ] Logout mengarah ke SLO, bukan ACS.

