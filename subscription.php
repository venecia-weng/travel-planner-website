<?php
/**
 * Subscription Section Template
 * Newsletter sign-up form
 */
?>
<!-- subscription section -->
<section class="py-5 theme-bg-primary">
    <div class="container">
        <div class="row justify-between items-center">
            <div class="col-12 col-lg-6">
                <div class="d-flex align-items-center">
                    <img src="assets/images/icons/subscribe-icon.png" alt="subscribe" class="img-fluid">
                    <div class="ms-3">
                        <h4 class="text-26 text-white fw-600"><?php echo $subscription_title ?? 'Your Travel Journey Starts Here'; ?></h4>
                        <p class="text-white"><?php echo $subscription_subtitle ?? 'Sign up and we\'ll send the best deals to you'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5 offset-lg-1 align-self-center">
                <form action="<?php echo $subscription_action ?? 'process-subscription.php'; ?>" method="post" class="subscription-form">
                    <div class="input-group subs-form">
                        <input type="email" name="email" class="form-control border-0" placeholder="Your Email" 
                               aria-label="Your Email" aria-describedby="button-addon2" required>
                        <button class="btn btn-search" type="submit" id="button-addon2">Subscribe</button>
                    </div>
                    
                    <?php if (isset($show_gdpr) && $show_gdpr): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="gdpr_consent" id="gdprConsent" required>
                        <label class="form-check-label text-white small" for="gdprConsent">
                            I agree to receive promotional emails and accept the <a href="privacy-policy.php" class="text-white text-decoration-underline">Privacy Policy</a>
                        </label>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</section>