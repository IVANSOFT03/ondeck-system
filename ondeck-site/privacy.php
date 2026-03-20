<?php
declare(strict_types=1);

$active_page = 'privacy';
require_once __DIR__ . '/partials/init.php';
$abs = htmlspecialchars($assets_base, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Política de Privacidad | OnDeck Colectivo</title>
    <?php require __DIR__ . '/partials/head-global.php'; ?>
    <link rel="stylesheet" href="<?php echo $abs; ?>/css/legal.css"/>
</head>
<body class="page-legal page-legal--privacy" data-page="privacy">
<?php require __DIR__ . '/partials/header.php'; ?>

<main class="legal-main">
    <div class="legal-main__bg-grid perspective-grid--legal" aria-hidden="true"></div>
    <div class="legal-main__blob-tr" aria-hidden="true"></div>
    <div class="legal-main__blob-bl" aria-hidden="true"></div>

    <article class="legal-article">
        <header class="legal-hero">
            <div class="legal-hero__rule-wrap">
                <span class="legal-hero__rule"></span>
                <span class="legal-hero__tag">Legal Document / REF_OD_2024</span>
            </div>
            <h1 class="legal-hero__title">
                Política de <br/>
                <span class="legal-hero__title-stroke">Privacidad</span>
            </h1>
            <p class="legal-hero__date">Última actualización: marzo 2026</p>
        </header>

        <div class="legal-sections">
            <section class="legal-block reveal">
                <div class="legal-block__inner">
                    <div class="legal-block__aside">
                        <span class="legal-block__label legal-block__label--p">01 // COLLECTION</span>
                        <h2 class="legal-block__h2">Información que recopilamos</h2>
                    </div>
                    <div class="legal-block__body">
                        <p class="legal-block__p">En el núcleo de OnDeck Colectivo, procesamos únicamente los datos esenciales para mantener la integridad del flujo creativo:</p>
                        <ul class="legal-block__list">
                            <li><span class="material-symbols-outlined" aria-hidden="true">check_circle</span><span>Identidad digital (Nombre y correo electrónico)</span></li>
                            <li><span class="material-symbols-outlined" aria-hidden="true">check_circle</span><span>Metadatos de archivos de Google Drive (Acceso de solo lectura)</span></li>
                            <li><span class="material-symbols-outlined" aria-hidden="true">check_circle</span><span>Telemetría de uso del sistema y logs de sesión</span></li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="legal-block legal-block--s2 reveal">
                <div class="legal-block__inner">
                    <div class="legal-block__aside">
                        <span class="legal-block__label legal-block__label--s">02 // UTILIZATION</span>
                        <h2 class="legal-block__h2">Cómo usamos la información</h2>
                    </div>
                    <div class="legal-block__body">
                        <p class="legal-block__p">Tus datos alimentan exclusivamente los mecanismos internos de nuestra infraestructura. Bajo ninguna circunstancia mercantilizamos tu huella digital.</p>
                        <div class="legal-mini-grid">
                            <div class="legal-mini-card">
                                <h4 class="legal-mini-card__h legal-mini-card__h--p">Queue Logic</h4>
                                <p class="legal-mini-card__p">Gestión automatizada de turnos en el sistema de carga y visualización.</p>
                            </div>
                            <div class="legal-mini-card">
                                <h4 class="legal-mini-card__h legal-mini-card__h--s">Alert Sync</h4>
                                <p class="legal-mini-card__p">Notificaciones instantáneas sobre el estado de tus activos digitales.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="legal-block reveal">
                <div class="legal-block__inner">
                    <div class="legal-block__aside">
                        <span class="legal-block__label legal-block__label--p">03 // ECOSYSTEM</span>
                        <h2 class="legal-block__h2">Servicios de terceros</h2>
                    </div>
                    <div class="legal-block__body">
                        <p class="legal-block__p">Nuestra plataforma se integra de manera granular con ecosistemas externos. Te recomendamos revisar sus protocolos de seguridad específicos:</p>
                        <div class="legal-eco">
                            <div class="legal-eco__chip glass-panel">
                                <span class="material-symbols-outlined" aria-hidden="true">cloud</span>
                                <span>Google Drive</span>
                            </div>
                            <div class="legal-eco__chip glass-panel">
                                <span class="material-symbols-outlined" aria-hidden="true">music_note</span>
                                <span>TikTok</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="legal-block legal-block--s3 reveal">
                <div class="legal-block__inner">
                    <div class="legal-block__aside">
                        <span class="legal-block__label legal-block__label--t">04 // TELEMETRY</span>
                        <h2 class="legal-block__h2">Cookies</h2>
                    </div>
                    <div class="legal-block__body">
                        <p class="legal-block__p">Implementamos únicamente balizas funcionales necesarias para la estabilidad de la sesión. Rechazamos categóricamente el rastreo publicitario intrusivo.</p>
                    </div>
                </div>
            </section>

            <section class="legal-block reveal">
                <div class="legal-block__inner">
                    <div class="legal-block__aside">
                        <span class="legal-block__label legal-block__label--p">05 // SOVEREIGNTY</span>
                        <h2 class="legal-block__h2">Tus derechos</h2>
                    </div>
                    <div class="legal-block__body">
                        <p class="legal-block__p">Mantienes la soberanía total sobre tus datos. En cualquier momento puedes ejecutar comandos de:</p>
                        <div class="legal-rights">
                            <div class="legal-rights__row"><span>Acceso a registros</span><span class="legal-rights__ok">GRANTED</span></div>
                            <div class="legal-rights__row"><span>Rectificación de identidad</span><span class="legal-rights__ok">GRANTED</span></div>
                            <div class="legal-rights__row"><span>Eliminación permanente</span><span class="legal-rights__warn">PURGE_ENABLED</span></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="legal-contact-block glass-panel reveal">
                <span class="material-symbols-outlined legal-contact-block__icon" aria-hidden="true">terminal</span>
                <span class="legal-block__label legal-block__label--p">06 // UPLINK</span>
                <h2 class="legal-contact-block__h2">Contacto</h2>
                <p class="legal-block__p">Si requieres una aclaración técnica sobre nuestros protocolos, establece comunicación directa:</p>
                <a class="legal-contact-block__a" href="mailto:contacto@ondeck.nodo-digital.com">contacto@ondeck.nodo-digital.com</a>
            </section>
        </div>
    </article>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
