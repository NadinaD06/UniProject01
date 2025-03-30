<footer class="main-footer">
        <div class="footer-container">
            <div class="footer-links">
                <a href="/views/about.php">About</a>
                <a href="/views/terms.php">Terms of Service</a>
                <a href="/views/privacy.php">Privacy Policy</a>
                <a href="/views/help.php">Help Center</a>
                <a href="/views/contact.php">Contact Us</a>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> ArtSpace. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <?php if (isset($page_js) && !empty($page_js)): ?>
    <script src="/assets/js/<?php echo $page_js; ?>.js"></script>
    <?php endif; ?>
</body>
</html>