    </main><!-- /main -->

    <!-- Footer -->
    <footer class="content-footer">
        <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?> &copy; <?php echo date('Y'); ?>
    </footer>

</div><!-- /content-wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Flatpickr JS + locale ES -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<!-- Custom JS -->
<?php $jsVer = @filemtime(BASE_PATH . '/assets/js/main.js') ?: APP_VERSION; ?>
<script src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo $jsVer; ?>"></script>

</body>
</html>
