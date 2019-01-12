layui.define(['layer', 'table', 'form'], function(exports){
	var $ = layui.jquery;
	var layer = layui.layer;
	var table = layui.table;
	var form = layui.form;
		
	table.render({
		elem: '#table',
		url: '/'+ADMIN_DIR+'/substationurl/ajax',
		page: true,
		cellMinWidth:60,
		cols: [[
			{field: 'id', title: 'ID', width:80},
			{field: 'url', title: '分站顶级域名'},
			{field: 'state', title: '状态',width:120,templet: '#checkboxTpl'},
			{field: 'opt', title: '操作', width:120, templet: '#opt',fixed: 'right',align:'center'},
		]]
	});

	//添加
	form.on('submit(add)', function(data){
		var i = layer.load(2,{shade: [0.5,'#fff']});
		$.ajax({
			url: '/'+ADMIN_DIR+'/substationurl/addajax',
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
    form.on('checkbox(lockDemo)', function(obj){
        $.ajax({
            url: '/'+ADMIN_DIR+'/substationurl/stateajax',
            type: 'POST',
            dataType: 'json',
            data: {
            	'id' : this.value,
            	'state' : obj.elem.checked ? '1' : '2'
			},
        })
            .done(function(res) {
                if (res.code === '0') {
                    layer.msg('状态更新成功',{icon:1,time:5000});
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
    });
	exports('adminsubstationurl',null)
});