<?php
return array(
	'random'=>array(//配置在表单中的键名 ,这个会是config[random]
		'title'=>'插件开关:',//表单的文字
		'type'=>'radio',		 //表单的类型：text、textarea、checkbox、radio、select等
		'options'=>array(		 //select 和radion、checkbox的子选项
			'1'=>'开启',		 //值=>文字
			'0'=>'关闭',
		),
		'value'=>'1',			 //表单的默认值
		'labelwidth'=>'150px',
		//textarea表单含有以下元素colsrows,password和text含有以下元素width，所有元素都含有标签宽度labelwidth
	),
	'showname'=>array(
		'title'=>'展示页的名称:',//表单的文字
		'type'=>'text',		 //表单的类型：text、textarea、checkbox、radio、select等
		'value'=>'下载地址',
		'labelwidth' => '150px',		 //表单的默认值
		'width'=>'350px',
		//textarea表单含有以下元素colsrows,password和text含有以下元素width，所有元素都含有标签宽度labelwidth
	),
	'linkanalyze'=>array(
                        'title'=>'网址识别：',
                        'type'=>'textarea',
                        'value'=>"本地下载|default|default.png|0\n百度网盘|pan.baidu.com|bd.png|0\n蓝奏云盘|pan.lanzou.com|lz.png|1\n盛天云盘|pan.stnts.com|st.png|0",
						'tip'=>'示例【网盘名称|地址链接|图标（图标放在static中，没有可不填）】：百度网盘|baidu.com/pan|bdp.png',
                    	'cols'=>'100',
						'rows'=>'6',
                          'labelwidth'=>'150px', 
						  
      ),
);
					