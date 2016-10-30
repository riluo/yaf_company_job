(function() {
    var locked = 0;
    $("form").on('submit', function (e) {
        e.preventDefault();
        if (locked) {
            return;
        }
        var userName = $("#userName").val();
        var password = $("#password").val();
        if (!userName || !password) {
            alertMsg('请输入用户名和密码！');
            return;
        }
        locked = 1;
        var url = '/wechat/home/loginact';
        var data = {
            "userName": $("#userName").val(),
            "password": $("#password").val(),
            "rememberMe": !!$("#rememberMe:checked").length
        };
        $.ajax({
                type: "GET",
                url: url,
                data: data,
                dataType: "json",
                success: function(data){
                    if (!data.code) {
                        location.href = "/wechat/home/index";
                    }
                    else {
                        alertMsg(data.msg);
                    }
                },
                complete: function() {
                    locked = 0;
                }
            });
    });

    $('#confirm').on('click', function() {
        closeMsg();
    });
    var alertMsg = function(msg) {
        $('.weui_dialog_alert').show();
        $('#err_msg').text(msg);
    };
    var closeMsg = function() {
        $('.weui_dialog_alert').hide();
    };
})();