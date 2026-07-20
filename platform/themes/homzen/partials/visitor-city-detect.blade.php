<script>
(function () {
    if (!window.SerikVisitorLocation) {
        return;
    }

    if (window.SerikVisitorLocation.hasStoredCity() && window.SerikVisitorLocation.getSessionLocation()) {
        return;
    }

    window.SerikVisitorLocation.detectCityInBackground();
})();
</script>
