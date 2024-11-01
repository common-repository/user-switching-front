if (typeof( ajax_data ) === 'undefined') {
    ajax_data = {
        'url': '',
        'nonce': ''
    }
}
jQuery(document).ready(function ($) {

    var input = $('#admin-bar-search-users'),
        i_results = $("#s_users")

    if( $(input).length ) {
        $(input).autoComplete({
            source: function (search) {
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    url: ajax_data.url,
                    data: 'action=search_in_users&s_users=' + search + '&security=' + ajax_data.nonce,
                    success: function (_data) {

                        $(i_results).empty()

                        var data = _data.data.to_complete,
                        error    = _data.data.error

                        if ( 'undefined' !== typeof(error) ) {
                            $(i_results)
                                .append("<p>" + error + "</p>")
                        }

                        var array = error ? [] : $.map(data, function (v) {
                                return {
                                    title: v.title,
                                    role: v.role,
                                    switch_url: v.switch_url
                                }
                            })

                        $(i_results)
                            .append('<ul id="wp-admin-bar-user_switching_front-default" class="ab-submenu">')

                        for (var i = 0, len = array.length; i < len; i++) {
                            var el  = array[i]
                            $(i_results)
                                .append("<li><a href='" + el.switch_url + "'>" + el.title + " (" + el.role + ")</a></li>")
                        }

                        $(i_results)
                            .append("</ul>")
                    },
                    error: function () {
                        response([]);
                    }
                })
            }
        })
    }
})