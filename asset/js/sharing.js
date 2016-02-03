(function ($) {

    $(document).ready(function() {
        $('#sharing-buttons').on('click', 'li#sharing-fb', function(e) {
            console.log('ok');
            FB.ui({
                method: 'share',
                href: sharingUrl,
              }, function(response){});
        });
    });

})(jQuery);
