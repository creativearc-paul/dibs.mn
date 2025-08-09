$(function () {

    var $importType = $('.js-dg-import-type');
    var $importTypeField = $importType.find('select');

    var $importFile = $('.js-dg-import-file');
    var $importFileField = $importFile.find('select');

    var $importChannel = $('.js-dg-import-channel');
    var $importChannelField = $importChannel.find('select');

    var initialType = $importTypeField.find(':selected').val();

    console.log(initialType, $importType, $importTypeField);

    $importTypeField.on('change', function (event) {
        var val = $(this).find(':selected').val();

        if (val === 'file') {
            $importFile
                .removeClass('hidden')
                .addClass('fieldset-required')
            ;

            $importChannel
                .addClass('hidden')
                .removeClass('fieldset-required')
            ;
        } else {
            $importFile
                .addClass('hidden')
                .removeClass('fieldset-required')
            ;

            $importChannel
                .removeClass('hidden')
                .addClass('fieldset-required')
            ;
        }
    });

    $importTypeField.trigger('change');

});
