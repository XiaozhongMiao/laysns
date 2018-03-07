layui.use(['form','upload'],function(){
  var form = layui.form,jq = layui.jquery;
  var upload = layui.upload;
  var url=jq('form').data('url');
  var locationurl=jq('form').attr('localtion-url');
  jq('.layui-form').append('<input type="hidden" name="token" value="'+token+'">');
  form.on('submit(formadd)', function(data){
	    loading = layer.load(2, {
	      shade: [0.2,'#000']
	    });
	
	  
	    var param = data.field;
	   
	    jq.post(url,param,function(data){
	  
	      if(data.code == 200){
	        layer.close(loading);
	        layer.msg(data.msg, {icon: 1, time: 1000}, function(){
	          location.href = locationurl;
	        });
	      }else{
	        layer.close(loading);
	        layer.msg(data.msg, {icon: 2, anim: 6, time: 1000});
	      }
	    });
	    return false;
	  });
 })