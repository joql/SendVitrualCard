layui.define(['layer', 'table', 'form'], function(exports){
	var $ = layui.jquery;
	var layer = layui.layer;
	var table = layui.table;
	var form = layui.form;


	table.render({
		elem: '#table',
		url: '/'+ADMIN_DIR+'/reportsubstation/ajax',
		page: true,
		cols: [[
			{field: 'url', title: '分站域名', minWidth:120},
			{field: 'order_today', title: '今日订单', minWidth:50},
			{field: 'income_today', title: '今日收入', minWidth:50},
			{field: 'profit_today', title: '今日净利润', minWidth:50},
			{field: 'order_month', title: '本月订单', minWidth:50},
			{field: 'income_month', title: '本月收入', minWidth:50},
			{field: 'profit_month', title: '本月净利润', minWidth:50},
			{field: 'order_all', title: '总计订单', minWidth:50},
			{field: 'income_all', title: '总计收入', minWidth:50, align:'center'},
			{field: 'profit_all', title: '总计净利润', minWidth:50, align:'center'},
		]]
	});
    form.on('submit(search)', function(data){
        table.reload('table', {
            url: '/'+ADMIN_DIR+'/reportsubstation/ajax',
            where: data.field
        });
        return false;
    });

	
	exports('adminreportsubstation',null)
});