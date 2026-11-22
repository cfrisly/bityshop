# Publicar Bityshop en internet (gratis)

**Dónde:** un servidor virtual de **Oracle Cloud "Always Free"** (gratis de forma
permanente), con un dominio gratuito de **DuckDNS** y HTTPS automático.

**Resultado:** `https://TU-NOMBRE.duckdns.org`, abierto para cualquier persona.

Tiempo estimado: 1 a 2 horas la primera vez.

---

## Paso 0. Prepara el proyecto en tu Mac

1. En la carpeta del proyecto hay archivos vacíos que se crearon por error desde la
   terminal: `customer`, `customer_id`, `id`, `type` y `getForeignKeyName`. Bórralos.
2. Verifica que todo funcione en tu Mac.
3. Sube todo a GitHub, incluida esta carpeta `deploy`:

```bash
cd ~/bityshop
git add -A
git commit -m "Preparar publicación"
git push
```

Tu `.env` **no** se sube (está en `.gitignore`), y está bien así.

---

## Paso 1. Crea tu cuenta de Oracle Cloud

1. Entra a <https://www.oracle.com/cloud/free/> → **Start for free**.
2. Oracle pide una **tarjeta de crédito o débito** solo para verificar tu identidad.
   No te cobra mientras uses recursos "Always Free". No acepta tarjetas prepago ni virtuales.
3. **Región principal (Home Region):** elige una cercana, por ejemplo
   *US East (Ashburn)* o una de México. **No se puede cambiar después.**

---

## Paso 2. Crea el servidor

En la consola: **Menú → Compute → Instances → Create instance**.

| Opción | Qué elegir |
|---|---|
| Name | `bityshop` |
| Image | **Canonical Ubuntu 24.04** |
| Shape | **Ampere → VM.Standard.A1.Flex**, 2 OCPU y 12 GB de memoria |
| Networking | Crear una red nueva (VCN) con **subred pública** y **asignar IPv4 pública** |
| SSH keys | **Generate a key pair for me** → **Save private key** |
| Boot volume | El tamaño predeterminado está bien |

Si aparece **"Out of capacity"**: prueba con 1 OCPU y 6 GB, cambia el
*Availability domain*, o elige el shape **VM.Standard.E2.1.Micro** (AMD, 1 GB;
más lento, pero funciona porque el script agrega memoria de intercambio).

Cuando la instancia esté en **Running**, anota su **Public IP address**.

Guarda la llave que descargaste:

```bash
mv ~/Downloads/ssh-key-*.key ~/.ssh/oracle-bityshop.key
chmod 600 ~/.ssh/oracle-bityshop.key
```

---

## Paso 3. Abre los puertos web en Oracle

**Networking → Virtual cloud networks → (tu VCN) → Security Lists → Default Security List
→ Add Ingress Rules**:

| Campo | Valor |
|---|---|
| Source CIDR | `0.0.0.0/0` |
| IP Protocol | TCP |
| Destination Port Range | `80,443` |

(El firewall interno del servidor lo abre el script automáticamente.)

---

## Paso 4. Consigue tu dominio gratis

1. Entra a <https://www.duckdns.org> e inicia sesión con GitHub o Google.
2. Crea un subdominio, por ejemplo `bityshop` → `bityshop.duckdns.org`
   (si está tomado, prueba otro).
3. En **current ip** escribe la IP pública del servidor y presiona **update ip**.

---

## Paso 5. Token de GitHub (solo si tu repositorio es privado)

GitHub → **Settings → Developer settings → Fine-grained tokens → Generate new token**:

- Repository access: **Only select repositories → bityshop**
- Permissions → **Contents: Read-only**

Copia el token; el script te lo pedirá. Si el repositorio es público, sáltate este paso.

---

## Paso 6. Instala todo en el servidor

Desde tu Mac, copia el script al servidor y conéctate:

```bash
cd ~/bityshop
scp -i ~/.ssh/oracle-bityshop.key deploy/servidor-instalar.sh ubuntu@IP_DEL_SERVIDOR:~
ssh -i ~/.ssh/oracle-bityshop.key ubuntu@IP_DEL_SERVIDOR
```

(La primera vez pregunta si confías en el servidor: escribe `yes`.)

Ya dentro del servidor:

```bash
sudo bash servidor-instalar.sh
```

Te pedirá el dominio, tu correo, el repositorio (presiona Enter para usar el tuyo),
la rama y el token. Tarda entre 5 y 15 minutos.

Al terminar, abre `https://TU-NOMBRE.duckdns.org`. La tienda aparece vacía:
es normal, todavía no tiene tus datos.

---

## Paso 7. Copia tus productos, imágenes y usuarios

En tu Mac (en otra ventana de Terminal, no en el servidor):

```bash
cd ~/bityshop
bash deploy/subir-datos.sh
```

Copia tu base de datos local y las imágenes de productos al servidor.
**Reemplaza la base del servidor**, así que úsalo solo para esta primera carga.

Si dice que no encuentra `mysqldump`: `brew install mysql-client` y vuelve a intentar.

---

## Paso 8. Que los correos lleguen de verdad (verificación de cuenta)

Mientras no configures esto, los correos solo quedan en el registro del servidor y
nadie podrá confirmar su cuenta.

Una opción gratuita es **Brevo** (<https://www.brevo.com>), que tiene un plan con
envío diario limitado (revisa el límite vigente en su sitio):

1. Crea la cuenta y verifica el correo remitente (**Senders**).
2. En **SMTP & API → SMTP**, genera una **SMTP key**.
3. En el servidor, edita el `.env`:

```bash
sudo nano /var/www/bityshop/.env
```

```
MAIL_MAILER=smtp
MAIL_DRIVER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=el-login-smtp-que-te-da-brevo
MAIL_PASSWORD=tu-smtp-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=el-correo-que-verificaste@ejemplo.com
```

Guarda con `Ctrl+O`, `Enter`, `Ctrl+X`, y aplica:

```bash
sudo -u www-data php /var/www/bityshop/artisan optimize:clear
```

---

## Paso 9. Seguridad antes de compartir el enlace

- **Cambia la contraseña del administrador** (entra a `/admin` con tu usuario y cámbiala),
  y borra los usuarios de prueba con contraseñas sencillas.
- Deja las **pasarelas de pago en modo de prueba**. No uses tarjetas reales.
- **Verificación de identidad:** durante las pruebas usa solo tus datos o los de
  personas que hayan aceptado. Las fotos de DPI son datos personales sensibles.
- `APP_DEBUG=false` ya viene configurado: no lo cambies en el servidor.

---

## Publicar cambios después

1. En tu Mac: `git add -A && git commit -m "descripción" && git push`
2. En el servidor:

```bash
ssh -i ~/.ssh/oracle-bityshop.key ubuntu@IP_DEL_SERVIDOR
sudo bash /var/www/bityshop/deploy/actualizar.sh
```

---

## Si algo falla

| Síntoma | Qué hacer |
|---|---|
| La página no abre | Revisa el paso 3 (reglas 80 y 443) y que DuckDNS tenga la IP correcta. |
| Error de certificado | Espera unos minutos; el certificado se crea solo cuando el dominio ya apunta al servidor. Revisa con `sudo journalctl -u caddy -n 50`. |
| "500 Server Error" | `sudo tail -n 50 /var/www/bityshop/storage/logs/laravel-*.log` |
| Faltan imágenes | Repite el paso 7, o revisa que exista `public/storage` (`sudo -u www-data php artisan storage:link`). |
| `composer install` se detiene por memoria | Vuelve a ejecutar el script; ya tiene swap de 2 GB. |

Compárteme el mensaje de error y lo revisamos.

---

## Para tener en cuenta

- Oracle puede **recuperar instancias gratuitas que pasan varios días casi sin uso**;
  entra a la tienda de vez en cuando mientras estés en pruebas.
- Haz una copia de la base de datos de vez en cuando:

```bash
sudo mysqldump bityshop | gzip > ~/respaldo-$(date +%F).sql.gz
```
