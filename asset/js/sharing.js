(function ($) {
    $(document).ready(function() {

        $('li#sharing-embed').on('click', 'a', function(e) {
            e.preventDefault();
            embedUrl = $(this).data('embed-url');
            embedCode = "<iframe src='" + embedUrl + "'></iframe>";
            alert(embedCode);
        });

        /**
         * Hide details summary on outside click.
         */
        $(document).on('click', function(e) {
            const clicked = e.target;
            $('details')
                .filter(function() {
                    return !$.contains(this, clicked);
                })
                .removeAttr('open');
        });

    });
})(jQuery);
