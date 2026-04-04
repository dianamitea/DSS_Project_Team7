<?php

?>

</main><!-- /#main-content -->

<footer class="footer" role="contentinfo">
    <div class="container">

        <div class="row g-5">

            <!-- Brand column -->
            <div class="col-12 col-lg-4">
                <a class="footer__brand" href="index.php">
                    Maison <span>Dorée</span>
                </a>
                <p class="footer__tagline">
                    "Baked with patience, served with love."
                </p>
                <p class="mt-3" style="font-size:0.85rem; color:rgba(245,236,215,0.50); max-width:32ch; line-height:1.7;">
                    Every loaf, pastry, and tart is crafted from scratch
                    using heritage grains and locally sourced ingredients.
                </p>

                <!-- Social icons -->
                <div class="footer__social d-flex gap-3 mt-4">
                    <a href="#" aria-label="Instagram" class="footer__social-link">
                        <i class="bi bi-instagram" aria-hidden="true"></i>
                    </a>
                    <a href="#" aria-label="Facebook" class="footer__social-link">
                        <i class="bi bi-facebook" aria-hidden="true"></i>
                    </a>
                    <a href="#" aria-label="Pinterest" class="footer__social-link">
                        <i class="bi bi-pinterest" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- Quick links -->
            <div class="col-6 col-sm-4 col-lg-2">
                <h6>Explore</h6>
                <nav aria-label="Footer navigation">
                    <a href="index.php">Home</a>
                    <!-- BUG 5 FIX: was menu.php → products.php -->
                    <a href="products.php">Our Menu</a>
                    <!-- BUG 5 FIX: was reservations.php → reservation.php -->
                    <a href="reservation.php">Reservations</a>
                    <a href="cart.php">Cart</a>
                </nav>
            </div>

            <!-- Account links -->
            <div class="col-6 col-sm-4 col-lg-2">
                <h6>Account</h6>
                <nav aria-label="Account navigation">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <a href="account.php">My Account</a>
                        <a href="orders.php">My Orders</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Contact / Opening hours -->
            <div class="col-12 col-sm-4 col-lg-4">
                <h6>Visit Us</h6>
                <address style="font-style:normal;">
                    <p class="mb-1 d-flex align-items-start gap-2">
                        <i class="bi bi-geo-alt mt-1" aria-hidden="true" style="color:var(--cafe-caramel);flex-shrink:0;"></i>
                        <span>12 Rue du Four, Old Town<br>Paris, France 75001</span>
                    </p>
                    <p class="mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-telephone" aria-hidden="true" style="color:var(--cafe-caramel);flex-shrink:0;"></i>
                        <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    </p>
                    <p class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-envelope" aria-hidden="true" style="color:var(--cafe-caramel);flex-shrink:0;"></i>
                        <a href="mailto:bonjour@maisondoree.fr">bonjour@maisondoree.fr</a>
                    </p>
                </address>

                <h6 class="mt-2">Opening Hours</h6>
                <table class="footer__hours" aria-label="Opening hours">
                    <tbody>
                        <tr>
                            <td>Mon – Fri</td>
                            <td>7:00 am – 7:00 pm</td>
                        </tr>
                        <tr>
                            <td>Saturday</td>
                            <td>7:30 am – 8:00 pm</td>
                        </tr>
                        <tr>
                            <td>Sunday</td>
                            <td>8:00 am – 5:00 pm</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div><!-- /.row -->

        <!-- Bottom bar -->
        <div class="footer__bottom">
            <span>&copy; <?= date('Y') ?> Maison Dorée. All rights reserved.</span>
            <span class="d-flex gap-3">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Use</a>
            </span>
        </div>

    </div><!-- /.container -->
</footer>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXOJMNbOOeCT2+J0MR2xFry5Yw3w=="
    crossorigin="anonymous">
</script>

<!-- Sticky navbar shrink on scroll -->
<script>
(function () {
    'use strict';
    const nav = document.getElementById('mainNav');
    if (!nav) return;
    const shrink = () => {
        if (window.scrollY > 60) {
            nav.classList.add('navbar--scrolled');
        } else {
            nav.classList.remove('navbar--scrolled');
        }
    };
    window.addEventListener('scroll', shrink, { passive: true });
    shrink();
}());
</script>

<!-- Auto-dismiss flash alerts after 5 s -->
<script>
(function () {
    'use strict';
    const flash = document.querySelector('.flash-banner');
    if (flash) {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(flash);
            if (bsAlert) bsAlert.close();
        }, 5000);
    }
}());
</script>

</body>
</html>
