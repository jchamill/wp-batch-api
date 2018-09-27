(function($) {
  'use strict';

  $(document).ready(function() {
    var $batch = $('#batchapi-status');
    if ($batch.length && $batch.hasClass('batchapi-ready')) {
      process(1, $batch.data('batch'));
    }
  });

  function process(step, batch_id) {
    $.ajax({
      type: 'POST',
      url: batchapi.ajax_url,
      data: {
        action: 'batch_process',
        step: step,
        batch_id: batch_id
      },
      dataType: 'json',
      success: function(response) {
        if('done' == response.step) {
          var $batch = $('#batchapi-status');
          var message = '<p>Import Complete!</p>';

          if (response.url) {
            //window.location = response.url;
            message += '<p><a href="' + response.url + '" class="button-primary">Continue</a></p>';
          }

          $batch.html(message);
        } else {
          $('#batchapi-status div').animate({
            width: response.percentage + '%',
          }, 50, function() {
            // Animation complete.
          });
          process(parseInt(response.step), batch_id);
        }
      }
    }).fail(function (response) {
      if ( window.console && window.console.log ) {
        console.log( response );
      }
    });
  }
})(jQuery);