<?php
declare(strict_types=1);

if (!isset($active_page)) {
    $active_page = 'index';
}
require_once __DIR__ . '/init.php';
$b = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');

switch ($active_page) {
    case 'privacy':
        ?>
<nav class="site-nav site-nav--privacy" aria-label="Principal">
    <div class="site-nav__inner">
        <div class="site-nav__start">
            <a class="site-nav__brand site-nav__brand--link" href="<?php echo $b; ?>/">OnDeck Colectivo</a>
            <div class="site-nav__links site-nav__links--tight">
                <a class="site-nav__link" href="<?php echo $b; ?>/#how-it-works">How it works</a>
                <a class="site-nav__link" href="<?php echo $b; ?>/#why-ondeck">Why OnDeck</a>
                <a class="site-nav__link" href="<?php echo $b; ?>/#statistics">Statistics</a>
                <a class="site-nav__link site-nav__link--active" href="<?php echo $b; ?>/privacy">Privacy</a>
                <a class="site-nav__link" href="<?php echo $b; ?>/terms">Terms</a>
            </div>
        </div>
        <div class="site-nav__actions">
            <button type="button" class="btn btn--join-privacy">Join Colectivo</button>
            <button type="button" class="site-nav__menu-btn" aria-label="Abrir menú">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
</nav>
        <?php
        break;

    case 'terms':
        ?>
<nav class="site-nav site-nav--terms" aria-label="Principal">
    <div class="site-nav__inner">
        <div class="site-nav__brand">OnDeck Colectivo</div>
        <div class="site-nav__links">
            <a class="site-nav__link" href="<?php echo $b; ?>/#how-it-works">How it works</a>
            <a class="site-nav__link" href="<?php echo $b; ?>/#why-ondeck">Why OnDeck</a>
            <a class="site-nav__link" href="<?php echo $b; ?>/#statistics">Statistics</a>
            <a class="site-nav__link" href="<?php echo $b; ?>/privacy">Privacy</a>
            <a class="site-nav__link site-nav__link--active" href="<?php echo $b; ?>/terms">Terms</a>
        </div>
        <div class="site-nav__actions">
            <button type="button" class="btn btn--join-terms">Join Colectivo</button>
            <span class="material-symbols-outlined site-nav__menu-icon" aria-hidden="true">menu</span>
        </div>
    </div>
</nav>
        <?php
        break;

    case 'index':
    default:
        ?>
<nav class="site-nav site-nav--home" aria-label="Principal">
    <div class="site-nav__inner">
        <div class="site-nav__start">
            <span class="site-nav__brand">OD Colectivo</span>
        </div>
        <div class="site-nav__links">
            <a class="site-nav__link site-nav__link--active" href="#how-it-works">How it works</a>
            <a class="site-nav__link" href="#why-ondeck">Why OnDeck</a>
            <a class="site-nav__link" href="#statistics">Statistics</a>
            <a class="site-nav__link" href="<?php echo $b; ?>/privacy">Privacy</a>
            <a class="site-nav__link" href="<?php echo $b; ?>/terms">Terms</a>
        </div>
        <div class="site-nav__actions">
            <button type="button" class="btn btn--primary btn--primary--sm">Join Colectivo</button>
            <button type="button" class="site-nav__menu-btn" aria-label="Abrir menú">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
</nav>
        <?php
        break;
}
