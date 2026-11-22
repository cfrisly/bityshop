# Bity Shop: guía de puesta en producción

Esta guía aplica a la rama `upgrade/vanilo6-laravel12` (Vanilo 6.1 y Laravel 12).

---

## 1. Requisitos del servidor

| Componente | Versión | Notas |
|---|---|---|
| PHP | **8.4** (o 8.5) | Vanilo 6 ya no funciona con PHP 8.3 |
| Extensiones PHP | `gd`, `intl`, `zip`, `pdo_mysql`, `bcmath`, `mbstring`, `xml`, `curl`, `exif` | `gd` genera las miniaturas de las imágenes |
| Base de datos | MySQL 8.4 (8.0 también funciona) o MariaDB 10.11+ | |
| Composer | 2.x | |
| Servidor web | Nginx o Apache con **HTTPS** | El *document root* tiene que ser la carpeta `public/` |
| Node.js | 18+ | Solo si vas a recompilar CSS/JS. Los archivos compilados ya están en `public/` |

**Opciones de hosting:**
- **VPS con panel** (Laravel Forge o Ploi sobre DigitalOcean, Vultr, Hetzner o AWS Lightsail). Es la opción recomendada: instala PHP 8.4, Nginx, MySQL, HTTPS gratis y despliegues desde GitHub.
- **Laravel Cloud.** Es administrado y se conecta directo a GitHub.
- **Hosting compartido (cPanel).** Solo sirve si ofrece PHP 8.4 y acceso SSH para usar Composer.

---

## 2. Primera instalación (servidor nuevo, base de datos vacía)

```bash
git clone https://github.com/cfrisly/bityshop.git
cd bityshop
git checkout upgrade/vanilo6-laravel12   # o main, una vez fusionada

composer install --no-dev --optimize-autoloader
cp .env.production.example .env          # completa los valores
php artisan key:generate

php artisan migrate --seed --force        # crea las tablas y carga los países
php artisan storage:link
php artisan make:superuser                # tu usuario admin (acepta el rol "admin")

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Permisos:** el usuario del servidor web (por ejemplo `www-data`) tiene que poder escribir en `storage/` y en `bootstrap/cache/`.

```bash
chown -R www-data:www-data storage bootstrap/cache
```

---

## 3. Si ya tienes una base de datos con la versión anterior (Vanilo 4)

La actualización **solo agrega** tablas y columnas: son 32 migraciones nuevas y ninguna borra datos. Se probó con una base v4 que tenía pedidos, clientes, direcciones y favoritos, y después de migrar los conteos de todas las tablas fueron idénticos.

1. **Respaldo primero:**
   ```bash
   mysqldump -u USUARIO -p bityshop > respaldo-antes-de-v6.sql
   ```
2. Verifica que el servidor tenga **PHP 8.4**.
3. Despliega el código nuevo y ejecuta:
   ```bash
   php artisan down
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
   php artisan up
   ```
4. Si algo falla, vuelve al código anterior y restaura el respaldo:
   ```bash
   mysql -u USUARIO -p bityshop < respaldo-antes-de-v6.sql
   ```

---

## 4. Despliegues posteriores (cada vez que subas cambios)

```bash
cd /ruta/bityshop
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan up
```

> Importante: con `config:cache` activo, los cambios en `.env` no se aplican hasta que vuelvas a ejecutar `php artisan config:cache`.

---

## 5. Lista de seguridad

- [ ] `APP_ENV=production` y **`APP_DEBUG=false`**. Con `true`, la barra de depuración y los errores muestran datos internos.
- [ ] Instalar con `composer install --no-dev`. Eso deja fuera Debugbar, Ignition y Dusk.
- [ ] `APP_URL` con `https://`. Así se fuerzan los enlaces https.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] Usuario de base de datos propio, distinto de `root`, con contraseña fuerte.
- [ ] Si usas Cloudflare o un balanceador, configura `$proxies` en `app/Http/Middleware/TrustProxies.php`. Sin eso, las URLs y las IPs salen mal.
- [ ] El archivo `.env` **no** debe estar dentro de `public/` ni subido a git.
- [ ] Cambia la contraseña de cualquier usuario de prueba antes de abrir la tienda.

---

## 6. Respaldos

- **Base de datos:** un `mysqldump` diario, guardado fuera del servidor (S3, Backblaze, Google Drive). Forge y Ploi lo configuran en un par de clics.
- **Imágenes de productos:** respalda la carpeta `storage/app/public`.
- **Prueba de restauración:** restaura un respaldo en una base de prueba de vez en cuando, para confirmar que funciona.

---

## 7. Tareas programadas y colas

Hoy no son necesarias: `QUEUE_DRIVER=sync` y no hay tareas en `app/Console/Kernel.php`.
Si más adelante agregas correos de pedido o tareas programadas, añade este cron:

```cron
* * * * * cd /ruta/bityshop && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Pendientes de negocio antes de abrir la tienda

Ninguno de estos puntos rompe nada, pero un cliente real los va a notar:

1. **Pasarela de pago.** Las pasarelas instaladas (Mollie, Netopia, EuPlatesc, Adyen y Braintree) son europeas o necesitan contrato. Para Guatemala:
   - **PayPal** ya tiene integración oficial (`vanilo/paypal`); solo falta poner las credenciales en `.env`.
   - Para pagar con tarjeta local (BAC, Visanet/Cybersource, Recurrente, etc.) hace falta un *driver* propio de Vanilo.
   - Un método "Pago contra entrega" o "Transferencia" se crea desde el admin, en *Payment methods*, con la pasarela **Null / Offline**.
2. **Nombre de la tienda.** Pon `APP_NAME="Bity Shop"` en `.env`. Hoy el encabezado dice "Vanilo Demo".
3. **Textos en inglés en la tienda:** "Add to cart", "Submit Order", "All Products", "Filters", "Apply", etc.
4. **Enlaces del pie de página.** Apuntan a `#`: Sobre nosotros, Términos, Privacidad y Cookies. Además "Faboritos" está mal escrito.
5. **Correos de confirmación de pedido.** Hoy no se envía ninguno al cliente ni al vendedor.
6. **Envíos e impuestos.** No hay métodos de envío ni IVA (12 %) configurados. Se crean desde el admin en *Shipping methods* y *Tax rates*.
7. **Sección "Tarjetas guardadas".** Está en el menú de la cuenta, pero todavía no hace nada.
8. **Zona horaria.** `APP_TIMEZONE=America/Guatemala`. Fíjala **antes** de recibir pedidos reales, porque cambiarla después desplaza las fechas ya guardadas.
