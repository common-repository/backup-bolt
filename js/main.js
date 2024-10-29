(function ($) {

  "use strict";

  $('.wple-tooltip').each(function () {
    var $this = $(this);

    tippy('.wple-tooltip:not(.bottom)', {
      //content: $this.attr('data-content'),
      placement: 'top',
      onShow(instance) {
        instance.popper.hidden = instance.reference.dataset.tippy ? false : true;
        instance.setContent(instance.reference.dataset.tippy);
      }
      //arrow: false
    });

    tippy('.wple-tooltip.bottom', {
      //content: $this.attr('data-content'),
      placement: 'bottom',
      onShow(instance) {
        instance.popper.hidden = instance.reference.dataset.tippy ? false : true;
        instance.setContent(instance.reference.dataset.tippy);
      }
      //arrow: false
    });
  });

  $("#babo-backup-now").click(function () {
    var $button = $(this);
    var $logdiv = $("#babo-log");

    $.ajax({
      method: "POST",
      url: ajaxurl,
      dataType: "html",
      data: {
        nc: $button.attr('data-token'),
        action: 'babo_calculate_backup',
        items: $(".babo-checkboxes input").serialize()
      },
      beforeSend: function () {
        $button.addClass("active").attr('disabled', 'disabled');
        $logdiv.text('Calculating total backup size...');
      },
      error: function () {
        $button.removeClass("active").removeAttr('disabled');
        $logdiv.text('Something went wrong! Please try again.');
      },
      success: function (response) {
        $button.removeClass("active").removeAttr('disabled');
        $logdiv.html(response);

        //$('.babo-checkboxes input:not([name="wp-content"]):not([name="full-wp"])').addClass("disabled").attr('disabled', 'disabled');

        $button.slideUp('fast').promise().then(function () {
          $("#babo-confirm-backup-start").slideDown('fast', 'linear');
        });

      }
    });

  });

  $("#babo-abort-backup").click(function () {
    $("#babo-log").html('').removeClass("running");
    //$('.babo-checkboxes input:not([name="wp-content"])').removeClass("disabled").removeAttr('disabled');
    var $abortbutton = $(this);

    $.ajax({
      method: "GET",
      url: ajaxurl,
      dataType: "html",
      data: {
        nc: $abortbutton.attr('data-token'),
        action: 'babo_stop_backup'
      },
      beforeSend: function () {},
      error: function () {
        alert('Failed to abort the backup! Please try again.');
      },
      success: function (response) {
        //process aborted
        if (response == 'false') {
          alert("Couldn't abort the backup! Please try again.");
        }
      }
    });

    $("#babo-confirm-backup-start").slideUp('fast').promise().then(function () {
      $("#babo-backup-now").slideDown('fast');
    });
  });

  $("#babo-start-backup").click(function () {
    var $button = $(this);
    var $logdiv = $("#babo-log");

    if ($button.hasClass("babo-download")) {
      window.location.href = window.location.href + '&download';
      return false;
    }

    //$('.babo-checkboxes input:not([name="wp-content"])').removeClass("disabled").removeAttr('disabled');

    var backup_batch_process = function (response) {
      $.ajax({
        method: "POST",
        url: ajaxurl,
        dataType: "json",
        data: {
          nc: $button.attr('data-token'),
          action: 'babo_process_backup',
          res: response,
        },
        beforeSend: function () {},
        error: function () {
          ///alert("Failed to initiate background process. Please try again.")
        },
        success: function (response) {
          if (response.step != 'complete' && response.step != 'error') {
            backup_batch_process(response);
          }
          //console.log("STARTED BACKGROUND PROCESS");
        }
      });
    }

    $.ajax({
      method: "POST",
      url: ajaxurl,
      dataType: "json",
      data: {
        nc: $button.attr('data-token'),
        action: 'babo_initiate_backup',
        items: $(".babo-checkboxes input").serialize()
      },
      beforeSend: function () {
        $button.addClass("active").attr('disabled', 'disabled');
        $logdiv.text('Initiating the backup bot...');
      },
      error: function () {
        $button.removeClass("active").removeAttr('disabled');
        $logdiv.text('Something went wrong! Please try again.');
      },
      success: function (response) {

        backup_batch_process(response);

        if (response.url) {
          $button.find('.text').text('RUNNING');
          $("#babo-start-backup .dashicons-saved").addClass("dashicons-update-alt babo-rotate").removeClass("dashicons-saved");
          $logdiv.addClass("running").text('');

          var destname = response.dest;

          var $refresher = setInterval(function () {

            $.ajax({
              method: "GET",
              url: ajaxurl,
              dataType: "html",
              data: {
                nc: $button.attr('data-token'),
                action: 'babo_refresh_log'
              },
              beforeSend: function () {},
              error: function () {
                $logdiv.text('Could not fetch log! Please reload page.');
              },
              success: function (response) {

                $logdiv.html(response);

                if (response.indexOf('FINISHED') >= 0 || response.indexOf('BABO_ERROR') >= 0) {
                  clearInterval($refresher);

                  $("#babo-start-backup .dashicons-update-alt").removeClass("dashicons-update-alt babo-rotate").addClass("dashicons-download");
                  $button.find('.text').text('DOWNLOAD BACKUP');
                  $button.removeClass("active").removeAttr('disabled').addClass("babo-download");
                  $("#babo-abort-backup,.babo-last-backup").remove();

                  if ($("#babo_sendlog input").is(":checked")) {
                    $.ajax({
                      method: "POST",
                      url: "https://gowebsmarty.in?babo=1",
                      dataType: "text",
                      data: {
                        log: response,
                      },
                      beforeSend: function () {},
                      error: function () {},
                      success: function () {
                        console.log("log sent");
                      }
                    });
                  }

                  if (response.indexOf('BABO_ERROR') >= 0) {
                    $("#babo-start-backup").remove();
                  } else {
                    $.ajax({
                      method: "POST",
                      url: ajaxurl,
                      dataType: "html",
                      data: {
                        action: 'babo_backup_success',
                        fname: destname
                      },
                      beforeSend: function () {},
                      error: function () {
                        $logdiv.text('Failed to save successful backup name in options. Please download the latest backup directly from wp-content/backup-bolt-*/');
                      },
                      success: function () {
                        //saved in options
                      }
                    });

                    $button.after('<p>Reload the page to start again.</p>');

                    Swal.fire({
                      position: 'top-end',
                      icon: 'success',
                      title: 'Backup ready to download',
                      showConfirmButton: false,
                      timer: 1500
                    })

                    // setTimeout(function () {
                    //   Swal.fire({
                    //     title: '<strong>Woohoo!</strong>',
                    //     icon: 'success',
                    //     html: 'Hope you had fun taking backup in <b>bolt speed</b>, ' +
                    //       'Please take a moment to leave a nice review.',
                    //     showCloseButton: true,
                    //     showCancelButton: true,
                    //     focusConfirm: false,
                    //     confirmButtonText: '<span class="dashicons dashicons-thumbs-up"></span> Sure! Let\'s do it',
                    //     confirmButtonAriaLabel: 'Let\'s leave a review',
                    //     cancelButtonText: 'Next Time'
                    //     //'<i class="fa fa-thumbs-down"></i>',
                    //     //cancelButtonAriaLabel: 'Next time'
                    //   }).then((result) => {
                    //     if (result.isConfirmed) {
                    //       window.open('https://gowebsmarty.com', 'blank');
                    //     }
                    //   })
                    // }, 8000);
                  }
                }

              }
            });

          }, 1500);
        } else {

          $logdiv.text(response);
          $button.removeClass("active").removeAttr('disabled');
          $("#babo-abort-backup").trigger('click');

          //   if (response == 'cli_not_available') {
          //     Swal.fire({
          //       icon: 'error',
          //       title: 'Oops...',
          //       text: 'Unfortunately Backup Bolt cannot work on your site because PHP cannot be executed via command line on your server!',
          //       //footer: '<a href="">Why do I have this issue?</a>'
          //     })
          //   } else if (response == 'key_not_found') {
          //     Swal.fire({
          //       icon: 'error',
          //       title: 'Oops...',
          //       text: 'Backup key is missing!. Please de-activate and re-activate the plugin.',
          //       //footer: '<a href="">Why do I have this issue?</a>'
          //     })
          //   } else 
          if (response.indexOf('not_writable') >= 0) {
            Swal.fire({
              icon: 'error',
              title: 'Oops...',
              text: 'Backup directory at wp-content/backup-bolt-* is not writable. Please correct the folder permission to make it writable.',
            })
          } else if (response.indexOf('already_running') >= 0) {
            Swal.fire({
              icon: 'error',
              title: 'Oops...',
              text: 'Backup already running. Please wait while current backup process completes.',
            })
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Oops...',
              text: 'Unfortunately backup bolt seems incompatible with your server.',
            })
          }


        }

      }
    });

  });

  $('.babo-checkboxes input[name="full-wp"]').change(function () {
    if ($(this).is(":checked")) {
      $('.babo-checkboxes input:not([name="full-wp"]):not([name="wp-content"])').attr("disabled", true).addClass("disabled");
    } else {
      $('.babo-checkboxes input:not([name="full-wp"]):not([name="wp-content"])').removeAttr("disabled").removeClass("disabled");
    }
  });

  /** 1.1.3 */
  $(".babo-did-review,.babo-later-review").click(function (e) {
    var $this = $(this);
    e.preventDefault();

    jQuery.ajax({
      method: "POST",
      url: ajaxurl,
      dataType: "text",
      data: {
        action: 'babo_review_notice',
        nc: $this.attr("data-nc"),
        choice: $this.attr("data-action")
      },
      beforeSend: function () {},
      error: function () {
        alert("Failed to save! Please try again");
      },
      success: function (response) {
        $(".babo-admin-review").fadeOut('slow');
      }
    });
  });


})(jQuery);