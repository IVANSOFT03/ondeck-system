<?php
declare(strict_types=1);

$active_page = 'index';
require_once __DIR__ . '/partials/init.php';
$abs = htmlspecialchars($assets_base, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>OnDeck Colectivo | La Primera Plataforma Colaborativa de TikTok</title>
    <?php require __DIR__ . '/partials/head-global.php'; ?>
    <link rel="stylesheet" href="<?php echo $abs; ?>/css/home.css"/>
</head>
<body class="page-home" data-page="home">
<canvas id="particles-canvas" class="particles-canvas" aria-hidden="true"></canvas>
<div class="cursor-dot" aria-hidden="true"></div>

<?php require __DIR__ . '/partials/header.php'; ?>

<header class="hero">
    <div class="hero__bg">
        <div class="hero__bg-gradient"></div>
        <div class="hero__bg-grid tron-grid"></div>
        <div class="hero__blob hero__blob--tl"></div>
        <div class="hero__blob hero__blob--br"></div>
    </div>
    <div class="container hero__grid">
        <div>
            <p class="hero__eyebrow"><span class="hero__pulse-dot hero__pulse" aria-hidden="true"></span><span data-typewriter="true" data-text="SYSTEM_INITIALIZED: V2.0"></span></p>
            <h1 class="hero__title">
                Tu contenido.<br/>
                <span class="glitch-text glitch-text--animated hero__title-accent">Nuestra cuenta.</span>
            </h1>
            <p class="hero__lead">La primera plataforma colaborativa de TikTok en español. Sube tu contenido y lo publicamos por ti. Escalabilidad sin fronteras.</p>
            <div class="hero__actions">
                <a class="btn-hero-primary" href="#contact">Quiero participar<span class="material-symbols-outlined" aria-hidden="true">bolt</span></a>
                <button type="button" class="btn-hero-ghost">Ver tutorial</button>
            </div>
        </div>
        <div class="hero-hud hero-hud__corners">
            <div class="hero-hud__panel glass-hud">
                <div class="hero-hud__row">
                    <p class="hero-hud__meta">COORD: 19.4326° N, 99.1332° W<br/>STATUS: BROADCASTING_ACTIVE</p>
                    <div class="hero-hud__tag">OD_01</div>
                </div>
                <div class="hero-hud__visual">
                    <div class="hero-hud__waveform" aria-hidden="true">
                        <span class="waveform-bar"></span>
                        <span class="waveform-bar"></span>
                        <span class="waveform-bar"></span>
                        <span class="waveform-bar"></span>
                        <span class="waveform-bar"></span>
                        <span class="waveform-bar"></span>
                        <span class="waveform-bar"></span>
                    </div>
                    <div class="hero-hud__img">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuDDUdLJJMzMwXXWvY1LcWI70k_0sIVOLhThjUC0MLJLb36LmrTUJu4PP7x3p4RjEL8aOPWVJrCnZpprRLg8Z6E7Zf9aH89PI46cTNbjbIoxeR94Gb-motWASeTcBRoo5E7Hu4kmEXeGvCuL01RansuvbrM5iBUe2vKm9C4sS434XWshPAr2JsO6GXEDLYqUhqakcYf7cSmQgauRP16Z9s9ixeXcSjAPdPJgNkKbEWxFwH9gIrjojyIEuOix02kLpqHf-Cpx1TWhq2i7" alt=""/>
                    </div>
                </div>
                <div class="hero-hud__stats">
                    <div>
                        <div class="hero-hud__stat-label">Global Traffic</div>
                        <div class="hero-hud__stat-value">89.4K</div>
                    </div>
                    <div>
                        <div class="hero-hud__stat-label">Uptime</div>
                        <div class="hero-hud__stat-value hero-hud__stat-value--sec">99.98%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="section-how reveal" id="how-it-works">
    <div class="container">
        <header class="section-how__head">
            <h2 class="section-how__title">How It Works</h2>
            <div class="section-how__rule"></div>
        </header>
        <div class="section-how__grid">
            <article class="step-card glass-hud reveal">
                <span class="step-card__num">01</span>
                <div class="step-card__icon-wrap"><span class="material-symbols-outlined step-card__icon step-card__icon--p" aria-hidden="true">chat_bubble</span></div>
                <h3 class="step-card__title">Escríbenos por DM</h3>
                <p class="step-card__text">Inicia el protocolo de contacto. Nuestro sistema te guiará a través de la interfaz de validación inicial.</p>
            </article>
            <article class="step-card step-card--s2 glass-hud reveal">
                <span class="step-card__num">02</span>
                <div class="step-card__icon-wrap"><span class="material-symbols-outlined step-card__icon step-card__icon--s" aria-hidden="true">cloud_upload</span></div>
                <h3 class="step-card__title">Sube tu video</h3>
                <p class="step-card__text">Carga tus activos multimedia a través de nuestro portal seguro. Optimización automática de metadatos.</p>
            </article>
            <article class="step-card step-card--s3 glass-hud reveal">
                <span class="step-card__num">03</span>
                <div class="step-card__icon-wrap"><span class="material-symbols-outlined step-card__icon step-card__icon--t" aria-hidden="true">schedule</span></div>
                <h3 class="step-card__title">Esperamos tu turno</h3>
                <p class="step-card__text">Nuestro algoritmo de cola inteligente procesa tu contenido para maximizar el impacto viral.</p>
            </article>
        </div>
    </div>
</section>

<section class="section-why reveal" id="why-ondeck">
    <div class="container">
        <div class="section-why__intro">
            <div>
                <h2 class="section-why__title">Why OnDeck Colectivo</h2>
                <p class="section-why__lead">Arquitectura diseñada para creadores que buscan impacto sin fricción técnica.</p>
            </div>
            <span class="badge-hud">SYSTEM_CORE_ADVANTAGES</span>
        </div>
        <div class="bento">
            <article class="bento-card bento__wide bento-card--key reveal">
                <span class="material-symbols-outlined bento-icon bento-icon--xl bento-icon--primary" aria-hidden="true">key_off</span>
                <h3 class="bento-card__title bento-card__title--lg">Sin acceso a contraseñas</h3>
                <p class="bento-card__text">Protocolo de seguridad Zero-Knowledge. Tu privacidad es innegociable en nuestro ecosistema.</p>
            </article>
            <article class="bento-card bento-card--sm reveal">
                <span class="material-symbols-outlined bento-icon bento-icon--lg bento-icon--secondary" aria-hidden="true">reorder</span>
                <h3 class="bento-card__title">Cola justa</h3>
                <p class="bento-card__text bento-card__text--xs">Algoritmo FIFO transparente. Todos los creadores tienen la misma prioridad de emisión.</p>
            </article>
            <article class="bento-card bento-card--sm reveal">
                <span class="material-symbols-outlined bento-icon bento-icon--lg bento-icon--tertiary" aria-hidden="true">dynamic_feed</span>
                <h3 class="bento-card__title">3 posts al día</h3>
                <p class="bento-card__text bento-card__text--xs">Mantenemos el pulso del algoritmo con publicaciones constantes y programadas.</p>
            </article>
            <article class="bento-card bento__full bento-card--cta reveal">
                <div>
                    <h3 class="bento-card__title bento-card__title--lg">Comunidad abierta</h3>
                    <p class="bento-card__text">Únete a cientos de creadores que ya están rompiendo el algoritmo.</p>
                </div>
                <button type="button" class="btn-bento-cta">Explorar Red</button>
            </article>
        </div>
    </div>
</section>

<section class="section-stats" id="statistics">
    <div class="section-stats__bg"><div class="tron-grid tron-grid--static"></div></div>
    <div class="container">
        <div class="section-stats__grid">
            <div class="stat-item reveal">
                <div class="stat-item__label">Participantes activos</div>
                <div class="stat-item__value stat-item__value--p" data-counter-target="128">0</div>
                <div class="stat-item__line stat-item__line--p"></div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-item__label">Videos publicados</div>
                <div class="stat-item__value stat-item__value--s" data-counter-target="347">0</div>
                <div class="stat-item__line stat-item__line--s"></div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-item__label">Países conectados</div>
                <div class="stat-item__value stat-item__value--t" data-counter-target="12">0</div>
                <div class="stat-item__line stat-item__line--t"></div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-item__label">Días activos</div>
                <div class="stat-item__value stat-item__value--p" data-counter-target="90">0</div>
                <div class="stat-item__line stat-item__line--p"></div>
            </div>
        </div>
    </div>
</section>

<section class="section-contact reveal" id="contact">
    <div class="section-contact__wrap container">
        <div class="contact-panel glass-hud">
            <span class="contact-panel__corner contact-panel__corner--tl"></span>
            <span class="contact-panel__corner contact-panel__corner--br"></span>
            <div class="contact-panel__inner">
                <p class="contact-panel__eyebrow">Finalize protocol</p>
                <h2 class="contact-panel__title">¿Quieres participar?</h2>
                <p class="contact-panel__text">Nuestro ecosistema está en constante expansión. Envíanos un mensaje para validar tu perfil y comenzar la secuencia de publicación.</p>
                <div class="contact-panel__actions">
                    <a class="btn-contact-tt" href="https://tiktok.com/@ondeckcolectivo" target="_blank" rel="noopener noreferrer">Ir a TikTok @ondeckcolectivo<span class="material-symbols-outlined" aria-hidden="true">trending_up</span></a>
                    <div>
                        <span class="contact-panel__mail-label">Official Communications</span><br/>
                        <a class="contact-panel__mail" href="mailto:contacto@ondeck.nodo-digital.com">contacto@ondeck.nodo-digital.com</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
