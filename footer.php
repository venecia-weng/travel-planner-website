<?php
/**
 * Footer template for RoundTours - Tours and Travel Landing Page
 * 
 * This file contains the footer section including links, copyright and scripts
 */
?>
    <!-- footer section-->
    <footer class="footer">
        <div class="container">
            <div class="row justify-content-between">
                <!-- Contact Us Section -->
                <div class="col-12 col-sm-6 col-lg-4 mb-5 mb-lg-0">
                    <h5 class="mb-4 fs-6">Contact Us</h5>
                    <div class="mb-4">
                        Customer Care<br>
                        <span class="fs-5 theme-text-primary">
                            <a href="tel:+6588775544" class="text-decoration-none">+(65) 8877 5544</a>
                        </span>
                    </div>
                    <div class="mb-4">
                        Need live support?<br>
                        <a href="mailto:roundtours319@gmail.com" class="fs-5 theme-text-primary">roundtours319@gmail.com</a>
                    </div>
                </div>
                
                <!-- Company Links Section -->
                <div class="col-12 col-sm-6 col-lg-4 mb-5 mb-lg-0">
                    <h5 class="mb-4 fs-6">Company</h5>
                    <ul class="fl-menu list-unstyled">
                        <?php
                        $menu_items = [
                            ['link' => 'about-us.php', 'text' => 'About Us'],
                            ['link' => 'destinations.php', 'text' => 'Destinations'],
                            ['link' => 'flights.php', 'text' => 'Flights'],
                            ['link' => 'hotels.php', 'text' => 'Hotels'],
                            ['link' => 'itinerary.php', 'text' => 'Itinerary'],
                            ['link' => 'blogs.php', 'text' => 'Blogs']        
                        ];
                        foreach ($menu_items as $item) {
                            $is_active = (basename($_SERVER['PHP_SELF']) == $item['link']) ? 'active' : '';
                            echo '<li class="nav-item mb-2"><a class="nav-link ' . $is_active . '" href="' . $item['link'] . '">' . $item['text'] . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <!-- Download App Section -->
                <div class="col-12 col-sm-6 col-lg-4 mb-5 mb-lg-0">
                    <h5 class="mb-4 fs-6">Download App</h5>
                    <?php
                    $app_stores = [
                        [
                            'icon' => 'assets/images/icons/play-icon.png',
                            'alt' => 'Google-Play',
                            'title' => 'Google-Play',
                            'text' => 'Google Play',
                            'link' => 'https://play.google.com/store/'
                        ],
                        [
                            'icon' => 'assets/images/icons/apple.png',
                            'alt' => 'apple',
                            'title' => 'apple',
                            'text' => 'App Store',
                            'link' => 'https://apps.apple.com/app/'
                        ]
                    ];

                    foreach ($app_stores as $index => $store) {
                        $margin_class = $index > 0 ? ' mt-3' : '';
                        echo '<a href="' . $store['link'] . '" class="d-inline-flex align-items-center border px-3 py-2 theme-border-radius min-w-150' . $margin_class . '" target="_blank" rel="noopener noreferrer">
                                <div class="flex-shrink-0">
                                    <img src="' . $store['icon'] . '" class="img-fluid" alt="' . $store['alt'] . '" title="' . $store['title'] . '">
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <p class="mb-0 small theme-text-accent-two">Get it on</p>
                                    <p class="mb-0 small theme-text-accent-one fw-bold">' . $store['text'] . '</p>
                                </div>
                            </a>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Footer Bottom - Copyright and Social -->
            <div class="row border-top mt-4 pt-4">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <p class="mb-0 small theme-text-accent-one">&copy; <?php echo date('Y'); ?> RoundTours All rights reserved.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex social justify-content-md-end">
                        <?php
                        $social_icons = [
                            ['icon' => 'facebook', 'link' => 'https://www.facebook.com/roundtours'],
                            ['icon' => 'twitter-x', 'link' => 'https://twitter.com/roundtours'],
                            ['icon' => 'linkedin', 'link' => 'https://www.linkedin.com/company/roundtours'],
                            ['icon' => 'instagram', 'link' => 'https://www.instagram.com/roundtours'],
                            ['icon' => 'whatsapp', 'link' => 'https://wa.me/6588775544']
                        ];
                        
                        foreach ($social_icons as $index => $social) {
                            $pe_class = ($index < count($social_icons) - 1) ? 'me-3' : '';
                            echo '<a href="' . $social['link'] . '" class="fs-4 ' . $pe_class . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-' . $social['icon'] . '"></i></a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll To Top Start-->
        <a href="javascript:void(0)" class="scrollToTop"><i class="bi bi-chevron-double-up"></i></a>
    </footer>

    <!-- js file -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script>
        // Scroll Top
        $(document).ready(function () {
            var ScrollTop = $(".scrollToTop");
            $(window).on('scroll', function () {
                if ($(this).scrollTop() < 500) {
                    ScrollTop.removeClass("active");
                } else {
                    ScrollTop.addClass("active");
                }
            });
            $('.scrollToTop').on('click', function () {
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
                return false;
            });
        });
    </script>
    <?php if (isset($extra_scripts) && !empty($extra_scripts)): ?>
        <?php echo $extra_scripts; ?>
    <?php endif; ?>
</body>
</html>