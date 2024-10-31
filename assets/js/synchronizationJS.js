syncFunction();

function syncFunction() {
    jQuery.ajax({
        url: "//"+document.domain+"/wp-content/plugins/realbigForWP/synchronising.php",
        data: {funcActivator: 'ready'},
        async: true,
        type: 'post',
        dataType: 'text',
    }).done(function (data) { })
}