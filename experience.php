<?php
/**
 * Experience Section Template
 * Showcases travel stats and promotional video
 */

// Stats data
$travel_stats = [
    ['icon' => 'airplane', 'count' => '4259', 'text' => 'Flights'],
    ['icon' => 'hospital', 'count' => '8289', 'text' => 'Hotels'],
    ['icon' => 'award', 'count' => '9789', 'text' => 'Packages'],
    ['icon' => 'star', 'count' => '9999', 'text' => 'Ratings']
];
?>
<!-- wonderful experience -->
<section class="experience">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="wrap">
                    <div class="row">
                        <div class="col-12 position-relative align-self-center">
                            <h4 class="display-4 theme-text-white mb-0 fw-bold text-center">Wonderful Travel
                                Experiences with<br>
                                <?php echo isset($site_name) ? $site_name : 'Round Tours'; ?></h4>
                            <div class="group custom-button">
                                <div class="d-flex align-items-center">
                                    <a href="<?php echo $video_url ?? 'https://www.youtube.com/watch?v=oNxCporOofo'; ?>"
                                        class="video-icon video-icon2 mr-30 ml-20 video_model">
                                        <i class="bi bi-play"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <?php foreach ($travel_stats as $stat): ?>
            <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-lg-0">
                <div class="d-flex align-items-center p-4 p-md-0">
                    <i class="bi bi-<?php echo $stat['icon']; ?> fs-4 theme-text-primary"></i>
                    <h3 class="fs-2 mb-0 mx-3"><?php echo $stat['count']; ?></h3>
                    <p class="fs-4 mb-0 theme-text-accent-one"><?php echo $stat['text']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>