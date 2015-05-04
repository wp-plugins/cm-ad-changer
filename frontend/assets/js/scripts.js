
(function ($) {

    $(document).ready(function () {
        $('.acc_banner_link').on("mousedown", function () {
            var data, $this;
            $this = $(this);
            data = {
                action: 'cmac_event_dispatcher',
                event: 'click',
                http_referer: document.URL,
                campaign_id: $this.data("campaign_id"),
                banner_id: $this.data("banner_id")
            };

            $.ajax({url: window.cmac_data.ajaxurl,
                type: "post",
                async: false,
                data: data}).done(function (response) {
                if (response.error)
                {
                    console.log(response);
                }
            });
        });

        $('.acc_banner_link').each(function (key, val) {
            var data, $this;
            $this = $(val);
            data = {
                action: 'cmac_event_dispatcher',
                event: 'impression',
                http_referer: document.URL,
                campaign_id: $this.data("campaign_id"),
                banner_id: $this.data("banner_id")
            };

            $.post(window.cmac_data.ajaxurl, data, function (response) {
                if (response.error)
                {
                    console.log(response);
                }
            });
        });

    });

})(jQuery);
