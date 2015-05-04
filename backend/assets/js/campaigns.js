var CM_AdsChanger = {};

(function ($) {

    CM_AdsChanger.delete_banner = function (obj) {
        if (obj.prevAll('img').attr('plupload_id')) {
            plupload_id = obj.prevAll('img').attr('plupload_id');
            CM_AdsChanger.uploader.removeFile(plupload_id);
        }

        obj.parent().fadeOut('slow', function () {
            if ($(this).hasClass('selected')) {
                $('#selected_banner').html('');
                $('#selected_banner_url').html('');
                $('.selected_banner_details').hide();
                $('.selected_banner_details input[type="hidden"]').val('');
            }
            $(this).remove();
        });
    };

    CM_AdsChanger.check_banner = function (obj) {
        obj.siblings().removeClass('selected');
        obj.addClass('selected');

        $('#selected_banner_url').html(obj.find('img.banner_image').attr('src').replace('tmp/', ''));

        if (obj.find('input[type="text"]').val() !== '')
            $('#selected_banner').html(obj.find('input[type="text"]').val());
        else
            $('#selected_banner').html('Untitled');

        $('.selected_banner_details input[type="hidden"]').val(obj.find('input[type="hidden"]').val());
        $('.selected_banner_details').show();
    };

    CM_AdsChanger.show_comment_error = function (obj, error_text) {
        obj.find('textarea').before('<div class="comment_error">' + error_text + '</div>');
    };

    $(document).ready(function () {

        $('#new_campaign_button').click(function () {
            document.location.href = base_url + '/wp-admin/admin.php?page=cmac_campaigns&acs_admin_action=new_campaign';
        });

        // uploader start

        CM_AdsChanger.uploader = new plupload.Uploader({
            runtimes: 'gears,html5,flash,silverlight,browserplus',
            browse_button: 'pickfiles',
            container: 'container',
            max_file_size: '10mb',
            url: ajaxurl + '?action=ac_upload_image',
            flash_swf_url: plugin_url + 'assets/js/plupload/plupload.flash.swf',
            silverlight_xap_url: plugin_url + 'assets/js/plupload/plupload.silverlight.xap',
            filters: [
                {title: "Image files", extensions: "jpg,jpeg,gif,png"}
            ]//,
//			resize : {width : 320, height : 240, quality : 90}
        });

        CM_AdsChanger.uploader.init();

        CM_AdsChanger.uploader.bind('FilesAdded', function (up, files) {
            up.refresh(); // Reposition Flash/Silverlight
            CM_AdsChanger.uploader.start();
        });

        CM_AdsChanger.uploader.bind('BeforeUpload', function (up, file) {
            if ($('.plupload_image').length >= banners_limit) {
                alert('Banners limit achieved: ' + banners_limit);
                up.stop();
            }
        });

        CM_AdsChanger.uploader.bind('FileUploaded', function (up, file, response) {
            var next_banner_index, filename, filename_parts, filename_without_ext, banner_title, html;

            var pattern = /^Error:/;
            if (!pattern.test(response.response)) {

                // incrementing next banner index to generate input id attribute (for label funcionate)
                next_banner_index++;

                // getting default name field value
                filename = file.name;
                filename_parts = filename.split('.');
                filename_without_ext = '';
                for(i = 0; i < filename_parts.length - 1; i++)
                    filename_without_ext += filename_parts[i];

                if (filename_without_ext.length > 20)
                    banner_title = filename_without_ext.substr(0, 19);
                else
                    banner_title = filename_without_ext;


                html = '<div class="plupload_image">';
                html += '<img src="' + upload_tmp_path + response.response + '" class="banner_image" plupload_id="' + file.id + '" />';
                html += '<input type="hidden" name="banner_filename[]" value="' + response.response + '" />';
                html += '<table class="banner_info">';
                html += '<tr>';
                html += '<td><label for="new_banner_title' + next_banner_index + '">Name</label><div class="field_help" title="' + label_descriptions.banner_title + '"></div></td>';
                html += '<td><input type="text" name="banner_title[]" id="new_banner_title' + next_banner_index + '" maxlength="150" value="' + banner_title + '" /></td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td><label for="new_banner_title_tag' + next_banner_index + '">Banner Title</label><div class="field_help" title="' + label_descriptions.banner_title_tag + '"></div></td>';
                html += '<td><input type="text" name="banner_title_tag[]" id="new_banner_title_tag' + next_banner_index + '" maxlength="150" /></td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td><label for="new_banner_alt_tag' + next_banner_index + '">Banner Alt</label><div class="field_help" title="' + label_descriptions.banner_alt_tag + '"></div></td>';
                html += '<td><input type="text" name="banner_alt_tag[]" id="new_banner_alt_tag' + next_banner_index + '" maxlength="150" /></td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td><label for="new_banner_link' + next_banner_index + '">Target URL</label><div class="field_help" title="' + label_descriptions.banner_link + '"></div></td>';
                html += '<td><input type="text" name="banner_link[]" id="new_banner_link' + next_banner_index + '" maxlength="150" /></td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td><label for="new_banner_weight' + next_banner_index + '">Weight</label><div class="field_help" title="' + label_descriptions.banner_weight + '"></div></td>';
                html += '<td><input type="text" name="banner_weight[]" id="new_banner_weight' + next_banner_index + '" maxlength="4" class="num_field" value="0" /></td>';
                html += '</tr>';
                html += '</table>';
                html += '<div class="ac_explanation clear">Click on image to select the banner</div>';
                html += '<div class="clicks_and_impressions">';
                html += '<div class="impressions">0</div>';
                html += '<div class="clicks">0</div>';
                html += '<div class="percent">0</div>';
                html += '</div>';
                html += '<img src="' + plugin_url + 'backend/assets/css/images/close.png' + '" class="delete_button" />';
                html += '</div>';

                $('#filelist').prepend(html);

                $('input#new_banner_weight' + next_banner_index).spinner({min: 0, max: 100, step: 10});

                $('.delete_button').eq(0).bind('click', function () {
                    CM_AdsChanger.delete_banner($(this));
                });

                $('.plupload_image img.banner_image').eq(0).bind('click', function () {
                    CM_AdsChanger.check_banner($(this).parent());
                });

                $('.plupload_image').eq(0).find('.field_help').tooltip({
                    show: {
                        effect: "slideDown",
                        delay: 100
                    },
                    position: {
                        my: "left top",
                        at: "right top"
                    }
                });

            } else {
                alert(response.response);
            }
        });

        $('.delete_button').click(function () {
            CM_AdsChanger.delete_banner($(this));
        });

        $('.plupload_image img.banner_image').click(function () {
            CM_AdsChanger.check_banner($(this).parent());
        });

        // uploader end

        // categories start

        $('#add_category').click(function (e) {
            e.preventDefault();
            if ($('.categories input[type="checkbox"]').length >= 10)
                return;
            if ($('.categories .category_row').length === 0) {
                $('.categories').empty();
            }

            $('.categories').append('<div class="category_row"><!--<input type="checkbox" aria-required="true" name="categories[]" value="" />&nbsp;' + "\n" + '--><input type="text" name="category_title[]" />' + "\n" + '<!--<input type="hidden" name="category_ids[]" value="" />' + "\n" + '--><a href="#" class="delete_link"><img src="' + plugin_url + 'backend/assets/css/images/close.png' + '" /></a></div>');

            $('.categories .delete_link').eq(-1).bind('click', function (e) {
                e.preventDefault();
                $(this).parent().remove();
                if ($('.categories .category_row').length === 0)
                {
                    $('.categories').html('There are no domain limitations set');
                }
            });
        });

        $('.categories .delete_link').click(function (e) {
            e.preventDefault();
            if (!confirm("Are you sure?\nThis will delete the category and it's relations to other campaigns"))
                return;
            $(this).parent().remove();
            if ($('.categories .category_row').length === 0)
            {
                $('.categories').html('There are no domain limitations set');
            }
        });

        $('#check_all_cats_link').click(function () {
            $('.categories').find('input[type="checkbox"]').attr('checked', 'checked');
        });

        $('#uncheck_all_cats_link').click(function () {
            $('.categories').find('input[type="checkbox"]').removeAttr('checked');
        });

        // categories end

        // dates
        $('#add_active_date_range').click(function (e) {
            var html;

            e.preventDefault();
            if ($('#dates .date_range_row').length >= 10)
                return;
            if ($('#dates .date_range_row').length === 0)
                $('#dates').empty();
            html = '<div class="date_range_row">';
            html += '<input type="text" name="date_from[]" class="date" />&nbsp;';
            html += '<input class="h_spinner ac_spinner" name="hours_from[]" value="0" />&nbsp;h&nbsp;';
            html += '<input class="m_spinner ac_spinner" name="mins_from[]" value="0" />&nbsp;m';
            html += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="' + plugin_url + '/assets/images/arrow_right.png' + '" style="vertical-align:bottom" />&nbsp;&nbsp;&nbsp;&nbsp;';
            html += '<input type="text" name="date_till[]" class="date" />&nbsp;';
            html += '<input class="h_spinner ac_spinner" name="hours_to[]" value="0" />&nbsp;h&nbsp;';
            html += '<input class="m_spinner ac_spinner" name="mins_to[]" value="0" />&nbsp;m&nbsp;';
            html += '<a href="#" class="delete_link"><img src="' + plugin_url + 'backend/assets/css/images/close.png' + '" /></a>';
            html += '</div>';
            $('#dates').append(html);
            $('#dates .date_range_row').eq(-1).find('input[type="text"]').datepicker();

            $('.date_range_row').eq(-1).find('.h_spinner').spinner({
                max: 24,
                min: 0
            });

            $('.date_range_row').eq(-1).find('.m_spinner').spinner({
                max: 50,
                min: 0,
                step: 10
            });

            $('#dates .delete_link').eq(-1).bind('click', function (e) {
                e.preventDefault();
                $(this).parent().remove();
                if ($('#dates .date_range_row').length === 0)
                    $('#dates').html('There are no date limitations set');
            });
        });

        $('#dates .date_range_row input[type="text"]').datepicker();

        $('.date_range_row .h_spinner').spinner({
            max: 24,
            min: 0
        });

        $('.date_range_row .m_spinner').spinner({
            max: 50,
            min: 0,
            step: 10
        });

        $('#dates .delete_link').click(function (e) {
            e.preventDefault();
            $(this).parent().remove();
            if ($('#dates .date_range_row').length === 0)
                $('#dates').html('There are no date limitations set');
        });

        $('.delete_campaign_link').click(function (e) {
            if (!confirm('Are you sure?')) {
                e.preventDefault();
                return false;
            }
        });

        $('#acs_div_wrapper').click(function () {
            if ($(this).attr('checked') === 'checked') {
                $('#class_name_fields').css('display', 'inline-block');
            } else {
                $('#class_name_fields').hide();
            }
        });

        $('#ac-fields').tabs();

        $('input[name^="banner_weight"]').spinner({min: 0, max: 100, step: 10});
        $('.plupload_image .banner_image').speechbubble();

    });
})(jQuery);
