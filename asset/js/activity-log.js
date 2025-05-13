$(document).ready(function () {

    $(document).on('o:sidebar-opened o:sidebar-closed', function() {
        window.setTimeout(function() {
            $(document).trigger("resize");
        }, 150);
    });

    // Focus the first input of the filters sidebar after clicking "View filters".
    $('#sidebar-filters').on('o:sidebar-opened', function(e) {
        setTimeout(() => {
            $(this).find('input:first').trigger('focus');
        }, 20); // Must wait until the sidebar is fully open before focusing.
    });

    // Focus the "View filters" button after closing the filters sidebar.
    $(document).on('click', '#sidebar-filters .sidebar-close', function(e) {
        $('.view-filters-button:first').trigger('focus');
    });

});
