<script>
    (function () {
        try {
            document.documentElement.dataset.theme = localStorage.getItem('rl-theme') === 'light' ? 'light' : 'dark';
        } catch (_) {
            document.documentElement.dataset.theme = 'dark';
        }
    }());
</script>
