<?php
declare(strict_types=1);

require_once __DIR__ . '/init.php';

$abs = htmlspecialchars($assets_base, ENT_QUOTES, 'UTF-8');
$siteOrigin = htmlspecialchars(ONDECK_SITE_ORIGIN, ENT_QUOTES, 'UTF-8');

$metaDescription = isset($meta_description)
    ? htmlspecialchars((string) $meta_description, ENT_QUOTES, 'UTF-8')
    : 'OnDeck Colectivo - La primera plataforma colaborativa de TikTok en español';

$tiktokVerification = isset($tiktok_site_verification)
    ? htmlspecialchars((string) $tiktok_site_verification, ENT_QUOTES, 'UTF-8')
    : 'AQUI_EL_CODIGO_DE_TIKTOK';
?>
    <meta name="description" content="<?php echo $metaDescription; ?>"/>
    <meta name="robots" content="index, follow"/>
    <meta property="og:title" content="OnDeck Colectivo"/>
    <meta property="og:url" content="<?php echo $siteOrigin; ?>"/>
    <meta property="og:image" content="/assets/img/og-image.jpg"/>
    <meta name="tiktok-developers-site-verification" content="<?php echo $tiktokVerification; ?>"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link rel="stylesheet" href="<?php echo $abs; ?>/css/main.css"/>
    <link rel="stylesheet" href="<?php echo $abs; ?>/css/animations.css"/>
