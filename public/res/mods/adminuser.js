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

    form.on('submit(search)', function(data){
        table.reload('table', {
            url: '/'+ADMIN_DIR+'/user/ajax',
            where: data.field
        });
        return false;
    });
	exports('adminuser',null)
});