layui.define(['layer', 'table', 'form','laydate'], function(exports){
	var $ = layui.jquery;
	var layer = layui.layer;
	var table = layui.table;
	var form = layui.form;
	var laydate = layui.laydate;

	laydate.render({
		elem: '#expire_time'
	})
	table.render({
		elem: '#table',
		url: '/'+ADMIN_DIR+'/substation/ajax',
		page: true,
		cellMinWidth:60,
		cols: [[
			{field: 'id', title: 'ID', width:80},
			{field: 'type_name', title: '类型'},
			{field: 'admin_name', title: '站长'},
			{field: 'admin_qq', title: '站长QQ'},
			{field: 'bind_url', title: '域名'},
			{field: 'remaining_sum', title: '余额'},
			{field: 'expire_time', title: '到期时间'},
			{field: 'state', title: '状态'},
			{field: 'opt', title: '操作', width:120, templet: '#opt',align:'center'},
		]]
	});

	//添加
	form.on('submit(add)', function(data){
        if(data.field.type_id == ""){
            layer.msg('请选择类型',{icon:2,time:5000});
            return false;
        }
		var i = layer.load(2,{shade: [0.5,'#fff']});
		$.ajax({
			url: '/'+ADMIN_DIR+'/substation/addajax',
			type: 'POST',
			dataType: 'json',
			data: data.field,
		})
		.done(function(res) {
			if (res.code == '1') {
				layer.open({
					title: '提示',
					content: '添加成功',
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
            url: '/'+ADMIN_DIR+'/products/ajax',
            where: data.field
        });
        return false;
    });
	exports('adminsubstationtype',null)
});