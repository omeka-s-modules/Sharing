(function ($) {
    $(document).ready(function() {

        $('li#sharing-embed').on('click', 'a', function(e) {
            e.preventDefault();
            embedUrl = $(this).data('embed-url');
            embedCode = "<iframe src='" + embedUrl + "'></iframe>";
            alert(embedCode);
        });
    });

})(jQuery);