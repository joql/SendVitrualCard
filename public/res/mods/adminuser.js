layui.define(['layer', 'table','form'], function(exports){
	var $ = layui.jquery;
	var layer = layui.layer;
	var table = layui.table;
	var form = layui.form;


	table.render({
		elem: '#table',
		url: '/'+ADMIN_DIR+'/user/ajax',
		page: true,
		cellMinWidth:60,
		cols: [[
			{field: 'id', title: 'ID', width:80},
			{field: 'substation_id', title: '分站ID', minWidth:160},
			{field: 'nickname', title: '用户名', minWidth:160},
            {field: 'money', title: '余额', minWidth:160},
			{field: 'email', title: '邮箱', minWidth:160},
			{field: 'qq', title: 'QQ', minWidth:160},
			{field: 'createtime', title: '注册时间', width:200, templet: '#createtime',align:'center'},
			{field: 'opt', title: '操作', width:200, templet: '#opt',align:'center',fixed: 'right', width: 160}
		]]
	});

    //修改
    form.on('submit(edit)', function(data){
        data.field.csrf_token = TOKEN;
        var i = layer.load(2,{shade: [0.5,'#fff']});
        $.ajax({
            url: '/'+ADMIN_DIR+'/user/editajax',
            type: 'POST',
            dataType: 'json',
            data: data.field,
        })
            .done(function(res) {
                if (res.code == '1') {
                    layer.open({
                        title: '提示',
                        content: res.msg,
                        btn: ['确定'],
                        yes: function(index, layero){
                            location.reload();
                        },
                        cancel: function(){
                            location.reload();
                        }
                    });
                } else {
                    layer.msg(res.msg,{icon:2,time:5000});
                }
            })
            .fail(function() {
                layer.msg('服务器连接失败，请联系管理员',{icon:2,time:5000});
            })
            .always(function() {
                layer.close(i);
            });

        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

    form.on('submit(search)', function(data){
        table.reload('table', {
            url: '/'+ADMIN_DIR+'/user/ajax',
            where: data.field
        });
        return false;
    });
	exports('adminuser',null)
});