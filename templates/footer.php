<?php
// ============================================
// templates/footer.php - PUBLIC WEBSITE FOOTER
// ============================================
if (!defined('APP_ROOT')) die('Direct access not permitted');
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><?php echo htmlspecialchars(get_setting('site_name', 'NewsHub')); ?></h5>
                    <p><?php echo htmlspecialchars(get_setting('site_description', 'Your trusted source for news')); ?></p>
                    
                    <div class="social-links">
                        <?php if ($fb = get_setting('facebook_url')): ?>
                        <a href="<?php echo htmlspecialchars($fb); ?>" class="social-link" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($tw = get_setting('twitter_url')): ?>
                        <a href="<?php echo htmlspecialchars($tw); ?>" class="social-link" target="_blank">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($ig = get_setting('instagram_url')): ?>
                        <a href="<?php echo htmlspecialchars($ig); ?>" class="social-link" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($li = get_setting('linkedin_url')): ?>
                        <a href="<?php echo htmlspecialchars($li); ?>" class="social-link" target="_blank">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">About</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Categories</h5>
                    <ul class="list-unstyled">
                        <?php
                        // Prioritize Politics category in footer if it exists
                        $footer_cats = [];
                        $politics_cat = db_fetch("SELECT * FROM categories WHERE slug = 'politics' AND status = 'active'");
                        if ($politics_cat) {
                            $footer_cats[] = $politics_cat;
                        }
                        $remaining = 5 - count($footer_cats);
                        if ($remaining > 0) {
                            $other_cats = db_fetch_all(
                                "SELECT * FROM categories WHERE status = 'active' AND slug != 'politics' LIMIT {$remaining}"
                            );
                            $footer_cats = array_merge($footer_cats, $other_cats);
                        }
                        foreach ($footer_cats as $cat):
                        ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $cat['slug']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/privacy.php">Privacy Policy</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/terms.php">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p><?php echo htmlspecialchars(get_setting('footer_text', '© 2025 NewsHub. All rights reserved.')); ?></p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>



