<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Db;
use app\common\model\PointNote as PointNoteModel;
use app\common\model\User as UserModel;
class PointNote extends AdminBase
{
	protected $commentmodel;
    protected function _initialize()
    {
        parent::_initialize();
        $this->PointNotemodel = new PointNoteModel();
    }
    public function index($keyword = '', $page = 1)
    {
    	$map = [];
		$usermodel=new UserModel();
		
		if ($keyword) {
			$map['p.uid']=0;
        	session('pointnotekeyword',$keyword);
			$mapn['username'] = ['like', "%{$keyword}%"];
    		$idarr=$usermodel->where($mapn)->column('id');
    		if(!empty($idarr)){
    			$map['p.uid']=array('in',$idarr);
    		}
        }else{
        
        	if(session('pointnotekeyword')!=''&&$page>1){
				$map['p.uid']=0;
				$mapn['username'] = ['like', "%".session('pointnotekeyword')."%"];
    			$idarr=$usermodel->where($mapn)->column('id');
    			if(!empty($idarr)){
    			$map['p.uid']=array('in',$idarr);
    		    }	
        	}else{
        		session('pointnotekeyword',null);
        	}  
        }

    
    	$tptc = $this->PointNotemodel->where($map)->alias('p')->join('user u', 'u.id=p.uid')->field('p.*,u.username')->order('p.id desc')->paginate(10);
    	$this->assign('tptc', $tptc);
    	return view();
    }
   
  public function delete($id)
    {
    	
    

    	
        if ($this->PointNotemodel->destroy($id)) {
            	return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
           return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function alldelete()
    {
    	$params = input('post.');
   
    	$ids = implode(',', $params['ids']);
    	  $result = $this->PointNotemodel->destroy($ids);
    	  if ($result) {
    	  	return json(array('code' => 200, 'msg' => '删除成功'));
    	  } else {
    	  	return json(array('code' => 0, 'msg' => '删除失败'));
    	  }
   }
}