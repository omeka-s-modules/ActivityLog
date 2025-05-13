(function ($) {
    var $doc = $(document);
    $doc.ready(function () {
        $doc.on('o:sidebar-opened o:sidebar-closed', function() {
            window.setTimeout(function() {
			    $doc.trigger("resize");
            }, 150);
        });
    });
})(jQuery);