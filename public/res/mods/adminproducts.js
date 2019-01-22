layui.define(['layer', 'table', 'form','layedit','upload'], function(exports){
	var $ = layui.jquery;
	var layer = layui.layer;
	var table = layui.table;
	var form = layui.form;
	var layedit = layui.layedit;
	var upload = layui.upload;
	
	var edit_description=layedit.build('description',{
		tool: ['strong','italic','underline','|','del','left','center','right','link','unlink','face']
	});	 //建立编辑器
		
	table.render({
		elem: '#table',
		url: '/'+ADMIN_DIR+'/products/ajax',
		page: true,
		cellMinWidth:60,
		cols: [[
			{field: 'id', title: 'ID', width:80},
			{field: 'typename', title: '商品类型'},
			{field: 'name', title: '商品名称'},
			{field: 'price', title: '售价',width:80},
			{field: 'qty', title: '库存', width:80, templet: '#qty',align:'center'},
			{field: 'sale_num', title: '销量', width:80, align:'center'},
			{field: 'auto', title: '发货模式', width:100, templet: '#auto',align:'center'},
			{field: 'active', title: '是否销售', width:100, templet: '#active',align:'center'},
			{field: 'pifa', title: '批发', width:80,templet: '#pifa',align:'center'},
			{field: 'opt', title: '操作', width:120, templet: '#opt',align:'center'},
		]]
	});

	form.on('radio(stockcontrol)', function(data){
		if(data.value=='1'){
			//$('#qty').val('0');
			var qty = $("#qty").attr("oldqty");
			$('#qty').val(qty);
			$("#qty").removeAttr("disabled");
		}else{
			$('#qty').val('0');
			$("#qty").attr("disabled","true");
		}
	});  
	
	form.on('radio(auto)', function(data){
		if(data.value=='1'){
			$('#addonsinput').hide();
		}else{
			$('#addonsinput').show();
		}
	});  

	form.verify({
		checkPrice: function (value, item) {
			if(value < Number($('#old_price').val())){
				return '当前价格不能低于成本价';
			}
        }
	});
	//更新库存
	$("#products_form").on("click","#updateQty",function(event){
		event.preventDefault();
		var pid = $("#pid").val();
		$(this).attr({"disabled":"disabled"});
        $.ajax({
            type: "POST",
            dataType: "json",
            url: '/'+ADMIN_DIR+'/products/updateqtyajax',
            data: { "csrf_token": TOKEN,'pid':pid},
            success: function(res) {
                if (res.code == 1) {
					layer.open({
						title: '提示',
						content: '更新成功',
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
                return;
            }
        });
	});
	
	//修改
	form.on('submit(edit)', function(data){
		layedit.sync(edit_description);
		data.field.csrf_token = TOKEN;
		data.field.description = layedit.getContent(edit_description);
		var i = layer.load(2,{shade: [0.5,'#fff']});
		$.ajax({
			url: '/'+ADMIN_DIR+'/products/editajax',
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

	$('#wholesale_add').on('click',function () {
        var _html;
        _html = '<div class="layui-inline" style="margin-left: 5px;">\n' +
            '                                    <div class="layui-form-mid">满</div>\n' +
            '                                    <div class="layui-input-inline">\n' +
            '                                        <input type="text" name="wholesale_num[]" lay-verify="number" autocomplete="off"  class="layui-input" placeholder="件数">\n' +
            '                                    </div>\n' +
            '                                    <div class="layui-form-mid">件单价为：</div>\n' +
            '                                    <div class="layui-input-inline">\n' +
            '                                        <input type="text" name="wholesale_price[]" lay-verify="checkPrice" autocomplete="off"  class="layui-input" placeholder="价格">\n' +
            '                                    </div>\n' +
            '                                    <div class="layui-form-mid">元/件</div> <div class="layui-input-inline">\n' +
            '                                        <button type="button" class="layui-btn layui-btn-danger" onclick="wholesale_del(this)">移除</button>\n' +
            '                                    </div>\n' +
            '                                </div>';
        $('.wholesale').append(_html);
    });

	window.wholesale_del = function (t) {
        layui.jquery(t).parent().parent().remove();
    }
    form.on('submit(search)', function(data){
        table.reload('table', {
            url: '/'+ADMIN_DIR+'/products/ajax',
            where: data.field
        });
        return false;
    });

    var uploadInst = upload.render({
        elem: '#photo_upload' //绑定元素
        ,url: '/'+ADMIN_DIR+'/products/photoUpload'//上传接口
		,accept: 'images'
        ,done: function(res){
            //上传完毕回调
			if(res.code == 1){
				$('#images').val(res.data);
			}
			layer.msg(res.msg);
        }
        ,error: function(){
            //请求异常回调
        }
    });
	exports('adminproducts',null)
});