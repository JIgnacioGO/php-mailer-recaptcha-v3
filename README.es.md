<a id="readme-top"></a>

<p align="right">
  <a href="./README.md">Read in English</a>
</p>

[![PHP][php-shield]][php-url]
[![reCAPTCHA][recaptcha-shield]][recaptcha-url]
[![PHPMailer][phpmailer-shield]][phpmailer-url]
[![Status][status-shield]][status-url]

<br />
<div align="center">
  <h1>reCAPTCHA v3 — Formulario de Contacto Seguro</h1>
  <p>
    Demo de formulario PHP protegido con Google reCAPTCHA v3, score en vivo y envío SMTP con PHPMailer.
    <br />
    <strong>Autor:</strong> JI Gutiérrez · <strong>Contexto:</strong> propuesta técnica / portafolio
    <br />
    <a href="#screenshots"><strong>Ver capturas</strong></a>
    ·
    <a href="#getting-started">Ejecutar localmente</a>
    ·
    <a href="#security">Seguridad</a>
  </p>
</div>

---

<details>
  <summary>Tabla de contenidos</summary>
  <ol>
    <li><a href="#about">Acerca del proyecto</a></li>
    <li><a href="#screenshots">Capturas de pantalla</a></li>
    <li><a href="#features">Funcionalidades</a></li>
    <li><a href="#security">Seguridad</a></li>
    <li><a href="#prerequisites">Prerrequisitos</a></li>
    <li><a href="#getting-started">Puesta en marcha</a></li>
    <li><a href="#configuration">Configuración</a></li>
    <li><a href="#built-with">Construido con</a></li>
    <li><a href="#project-structure">Estructura del proyecto</a></li>
    <li><a href="#how-it-works">Cómo funciona</a></li>
    <li><a href="#repository-use">Uso del repositorio</a></li>
  </ol>
</details>

---

<a id="about"></a>

## Acerca del proyecto

Formulario de contacto autocontenido que integra Google reCAPTCHA v3 para protección
anti-bot sin fricción para el usuario. El visitante nunca resuelve puzzles ni captchas
visibles — reCAPTCHA evalúa su comportamiento en segundo plano y asigna un score de
confianza de 0.0 (bot) a 1.0 (humano).

El proyecto incluye:

- Widget de score en vivo que se actualiza cada 25 segundos.
- Formulario funcional que envía correo real vía SMTP (PHPMailer).
- Tema claro / oscuro con detección automática del sistema operativo.
- Selector de idioma español / inglés.
- Cabeceras de seguridad (CSP con nonce, CSRF, rate limiting).

Diseñado como demo técnico publicable: un solo `web.php` autocontenido que se despliega
sin build tools, sin frameworks pesados y sin base de datos.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="screenshots"></a>

## Capturas de pantalla

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/01-light-es.png" alt="Tema claro en español" /></td>
    <td width="50%"><img src="docs/screenshots/02-dark-es.png" alt="Tema oscuro en español" /></td>
  </tr>
  <tr>
    <td align="center"><strong>Tema claro — Español</strong><br />Score en vivo, formulario y sección explicativa.</td>
    <td align="center"><strong>Tema oscuro — Español</strong><br />Paleta Deep Navy con indicadores azules.</td>
  </tr>
  <tr>
    <td width="50%"><img src="docs/screenshots/03-light-en.png" alt="Tema claro en inglés" /></td>
    <td width="50%"><img src="docs/screenshots/04-score-detail.png" alt="Detalle del score" /></td>
  </tr>
  <tr>
    <td align="center"><strong>Tema claro — English</strong><br />Cambio de idioma sin recarga de página.</td>
    <td align="center"><strong>Score y formulario</strong><br />Indicador visual con gauge, umbral y estado del envío.</td>
  </tr>
</table>

Las capturas viven en `docs/screenshots/`.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="features"></a>

## Funcionalidades

1. **reCAPTCHA v3 invisible** — evaluación de comportamiento sin puzzles.
2. **Score en vivo** — gauge circular que consulta Google cada 25s con control de visibilidad (pausa en pestaña inactiva).
3. **Envío SMTP real** — correo HTML + texto plano vía PHPMailer.
4. **Tema claro / oscuro** — toggle manual + detección automática de `prefers-color-scheme`, persistido en `localStorage`.
5. **Bilingüe (ES/EN)** — sistema i18n client-side con `data-i18n`, detección de `navigator.language`.
6. **CSRF token** — token de sesión de un solo uso con `hash_equals()`.
7. **Rate limiting** — máximo 1 envío cada 30 segundos por sesión.
8. **CSP con nonce** — scripts inline protegidos con nonce por request, sin `unsafe-inline` en `script-src`.
9. **Validación server-side** — sanitización de entrada, escape de salida, verificación de score y acción.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="security"></a>

## Seguridad

| Capa | Implementación |
|:-----|:---------------|
| Anti-bot | reCAPTCHA v3 con verificación server-side del token, score y action |
| CSRF | Token de sesión único, validado con `hash_equals()`, invalidado tras uso |
| Rate limiting | 1 envío / 30s por sesión |
| CSP | `script-src` con nonce + `strict-dynamic`; `frame-src`, `connect-src`, `font-src` restringidos |
| Headers | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy` |
| Input | Longitud máxima por campo, sanitización de newlines, `htmlspecialchars` en output |
| Configuración | Secretos en `config.local.php` (excluido de git), soporte de variables de entorno |
| PHP | `declare(strict_types=1)` en todos los archivos |

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="prerequisites"></a>

## Prerrequisitos

- [PHP](https://www.php.net/) >= 8.1 con extensiones `curl`, `mbstring`, `json`
- [Composer](https://getcomposer.org/)
- Claves de [Google reCAPTCHA v3](https://www.google.com/recaptcha/admin) (site key + secret key)
- Cuenta SMTP para envío de correo (Gmail, Mailtrap, etc.)

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="getting-started"></a>

## Puesta en marcha

```bash
# 1. Clonar el repositorio
git clone <repo-url>
cd kubiprint-recaptcha

# 2. Instalar dependencias
composer install

# 3. Crear configuración local
cp config.local.example.php config.local.php
# Editar config.local.php con tus claves reales

# 4. Iniciar servidor de desarrollo
php -S localhost:8000
```

Abrir `http://localhost:8000/web.php` en el navegador.

| URL | Descripción |
|:----|:------------|
| `http://localhost:8000/web.php` | Formulario con score en vivo |
| `http://localhost:8000/score.php` | Endpoint JSON del score (POST) |
| `http://localhost:8000/contact.php` | Procesador del formulario (POST) |

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="configuration"></a>

## Configuración

Copia `config.local.example.php` como `config.local.php` y completa los valores:

```php
define('APP_ENV', 'development');        // 'production' en despliegue real

define('RECAPTCHA_SITE_KEY', '...');     // Clave pública reCAPTCHA v3
define('RECAPTCHA_SECRET_KEY', '...');   // Clave secreta reCAPTCHA v3
define('RECAPTCHA_SCORE_THRESHOLD', 0.5); // 0.0 a 1.0

define('CONTACT_EMAIL', '...');          // Destinatario del formulario
define('SMTP_HOST', '...');              // ej: smtp.gmail.com
define('SMTP_PORT', 587);                // 587 (STARTTLS) o 465 (SMTPS)
define('SMTP_USERNAME', '...');
define('SMTP_PASSWORD', '...');
define('SMTP_ENCRYPTION', 'tls');        // 'tls', 'ssl' o ''
```

También soporta variables de entorno (`getenv()` / `$_ENV`). `config.local.php` tiene
prioridad sobre variables de entorno.

> **Importante:** `config.local.php` está en `.gitignore`. Nunca lo subas al repositorio.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="built-with"></a>

## Construido con

| Herramienta | Versión | Rol |
|:------------|:--------|:----|
| PHP | 8.1 | Backend y renderizado |
| Google reCAPTCHA v3 | current | Protección anti-bot |
| PHPMailer | 7.x | Envío SMTP |
| Composer | current | Gestión de dependencias |
| CSS Custom Properties | — | Temas claro/oscuro |
| Vanilla JS | ES5 | i18n, score widget, tema |

Sin frameworks frontend. Sin build tools. Sin base de datos.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="project-structure"></a>

## Estructura del proyecto

```text
kubiprint-recaptcha/
├── config.php                  # Carga de configuración (env + local)
├── config.local.example.php    # Plantilla de configuración local
├── recaptcha.php               # Helper compartido de verificación reCAPTCHA
├── web.php                     # Frontend: HTML + CSS + JS (autocontenido)
├── contact.php                 # Procesador del formulario (POST)
├── score.php                   # Endpoint JSON de score en vivo (POST)
├── composer.json               # Dependencia: PHPMailer
├── composer.lock               # Versiones fijadas de dependencias
├── .gitignore                  # Excluye secretos, vendor/, IDE
├── README.md                   # Documentación (inglés)
├── README.es.md                # Documentación (español)
└── docs/
    └── screenshots/            # Capturas para el README
```

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="how-it-works"></a>

## Cómo funciona

```text
┌─────────────┐     ┌──────────────┐     ┌─────────────────┐
│  Navegador   │────>│   web.php    │     │  Google reCAPTCHA│
│              │<────│  (frontend)  │     │    siteverify    │
└──────┬───────┘     └──────────────┘     └────────┬────────┘
       │                                           │
       │  1. reCAPTCHA JS evalúa comportamiento     │
       │  2. grecaptcha.execute() genera token      │
       │                                           │
       ├──── POST token ────> score.php ──────────>│
       │<─── JSON {score} ── score.php <───────────│
       │                                           │
       │  3. Usuario envía formulario               │
       │                                           │
       ├──── POST form ─────> contact.php ────────>│
       │                      │ verifica token      │
       │                      │ valida score >= thr │
       │                      │ valida action       │
       │                      │ envia email (SMTP)  │
       │<──── redirect ────── contact.php           │
       │      ?status=success                       │
```

1. Al cargar `web.php`, reCAPTCHA v3 comienza a evaluar el comportamiento del visitante.
2. Cada 25s, el frontend solicita un token y lo envía a `score.php`, que consulta a Google y devuelve el score.
3. Al enviar el formulario, se genera un nuevo token con action `contact_form`, se valida en `contact.php` contra Google, y si el score supera el umbral, se envía el correo vía PHPMailer.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

<a id="repository-use"></a>

## Uso del repositorio

Este repositorio se publica como demo técnico y propuesta de implementación.
No representa un producto comercial en producción.

<p align="right">(<a href="#readme-top">volver arriba</a>)</p>

---

[php-shield]: https://img.shields.io/badge/PHP-8.1-777BB4?style=flat-square&logo=php&logoColor=white
[php-url]: https://www.php.net/
[recaptcha-shield]: https://img.shields.io/badge/reCAPTCHA-v3-4285F4?style=flat-square&logo=google&logoColor=white
[recaptcha-url]: https://developers.google.com/recaptcha/docs/v3
[phpmailer-shield]: https://img.shields.io/badge/PHPMailer-7.x-0078D4?style=flat-square&logo=minutemailer&logoColor=white
[phpmailer-url]: https://github.com/PHPMailer/PHPMailer
[status-shield]: https://img.shields.io/badge/status-Demo%20funcional-2ea44f?style=flat-square
[status-url]: #about
