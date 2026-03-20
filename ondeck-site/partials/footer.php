<?php
declare(strict_types=1);

if (!isset($active_page)) {
    $active_page = 'index';
}
require_once __DIR__ . '/init.php';
$b = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');
$abs = htmlspecialchars($assets_base, ENT_QUOTES, 'UTF-8');

switch ($active_page) {
    case 'privacy':
        ?>
<footer class="site-footer site-footer--privacy" role="contentinfo">
    <div class="site-footer__inner">
        <div>
            <div class="site-footer__brand">OnDeck Colectivo</div>
            <p class="site-footer__blurb">Connecting the digital avant-garde through high-fidelity data streams and community-driven content cycles.</p>
        </div>
        <div class="site-footer__links">
            <a class="site-footer__link site-footer__link--active" href="<?php echo $b; ?>/privacy">Privacy Policy</a>
            <a class="site-footer__link" href="<?php echo $b; ?>/terms">Terms of Service</a>
            <a class="site-footer__link" href="<?php echo $b; ?>/#contact">Contact</a>
            <a class="site-footer__link site-footer__link--row" href="https://www.tiktok.com" rel="noopener noreferrer" target="_blank">
                <span class="material-symbols-outlined" aria-hidden="true">bolt</span> TikTok
            </a>
            <a class="site-footer__link site-footer__link--row" href="https://www.instagram.com" rel="noopener noreferrer" target="_blank">
                <span class="material-symbols-outlined" aria-hidden="true">photo_camera</span> Instagram
            </a>
        </div>
        <div class="site-footer__legal-note">
            <p class="site-footer__meta">
                © 2024 OnDeck Colectivo. <br class="footer-br"/> All rights reserved. <span class="site-footer__ref">REFR_OD_2024</span>
            </p>
        </div>
    </div>
</footer>
        <?php
        break;

    case 'terms':
        ?>
<footer class="site-footer site-footer--terms" role="contentinfo">
    <div class="site-footer__inner">
        <div class="site-footer__brand site-footer__brand--mono">OnDeck Colectivo</div>
        <div class="site-footer__links">
            <a class="site-footer__link" href="<?php echo $b; ?>/privacy">Privacy Policy</a>
            <a class="site-footer__link site-footer__link--active" href="<?php echo $b; ?>/terms">Terms of Service</a>
            <a class="site-footer__link" href="<?php echo $b; ?>/#contact">Contact</a>
            <a class="site-footer__link" href="https://www.tiktok.com" rel="noopener noreferrer" target="_blank">TikTok</a>
            <a class="site-footer__link" href="https://www.instagram.com" rel="noopener noreferrer" target="_blank">Instagram</a>
        </div>
        <div class="site-footer__meta site-footer__meta--small">© 2024 OnDeck Colectivo. All rights reserved. REFR_OD_2024</div>
    </div>
</footer>
        <?php
        break;

    case 'index':
    default:
        ?>
<footer class="site-footer site-footer--index" role="contentinfo">
    <div class="site-footer__inner">
        <div class="site-footer__row">
            <span class="site-footer__brand site-footer__brand--mono">OD Colectivo</span>
            <span class="site-footer__sep">|</span>
            <span class="site-footer__meta">© 2024 OnDeck Colectivo. All rights reserved. REFR_OD_2024</span>
        </div>
        <div class="site-footer__links">
            <a class="site-footer__link" href="<?php echo $b; ?>/privacy">Privacy Policy</a>
            <a class="site-footer__link" href="<?php echo $b; ?>/terms">Terms of Service</a>
            <a class="site-footer__link" href="#contact">Contact</a>
            <a class="site-footer__link" href="https://www.tiktok.com" rel="noopener noreferrer" target="_blank" aria-label="TikTok">
                <img class="site-footer__social-img" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAkzIkqV4myCxYSb8KHnLRuTpiwrci05YipStU6L8EvWu1oVBBK3VN2kIIADfjwvCDFN7SWPNOQOdbSa6SONv6wg7vo61CXs7Cke0VsDe7yrI7lD7oXjKkgjOXilfhaZagBIUOLe45BmM8e5UGIUta67tn3AKH8c2GcmoQrt0AcHVIEA9-ZARVVIC84W9QBUrO87Qk_mGpssVA7KiqCfqFy8kTYd9rmppg2II3oKP1nPbACScRI0EyDo7alQK4Q21qeURJa5U71iXB3" alt="" width="16" height="16"/>
            </a>
            <a class="site-footer__link" href="https://www.instagram.com" rel="noopener noreferrer" target="_blank" aria-label="Instagram">
                <img class="site-footer__social-img" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDjbsg7l9evufPp1BeFFMEwKvQnsHQeu9YUYWpdlb_FxpToYTrqpODKYyqKD55HgLq3QEJEIxEunDt2ifWX67IP6Umh5wgegqQzPo0V-0wi-3pYEFm9ccsySMhssWZ66FsBFGBxhgZJrDq4W3JpzBMSs4MIo5I7jepo6z09TjpySRfgQx-fJL1X_Hu0OTxnuVqMU_9_LhLDhYCgRkUO0lpY2eWPcg7TavDfWH9NSW8KYNNaKzasGW5Xx2kFBVbWIQOEOdxycySnca3E" alt="" width="16" height="16"/>
            </a>
        </div>
    </div>
</footer>
        <?php
        break;
}
?>
<script type="module" src="<?php echo $abs; ?>/js/main.js"></script>
