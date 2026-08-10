    </main>

    <div id="toast" class="toast" role="alertdialog" aria-live="assertive" aria-hidden="true">
        <div class="toast__card">
            <span id="toastIcon" class="toast__icon" aria-hidden="true">&check;</span>
            <p id="toastMessage" class="toast__message"></p>
        </div>
    </div>

    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>