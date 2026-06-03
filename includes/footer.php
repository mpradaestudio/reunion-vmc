    </main>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-5">
        <div class="container">
            <small class="text-muted"><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?> | &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS (con cache-busting por fecha de modificación) -->
    <?php $jsVer = @filemtime(BASE_PATH . '/assets/js/main.js') ?: APP_VERSION; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo $jsVer; ?>"></script>
</body>
</html>
