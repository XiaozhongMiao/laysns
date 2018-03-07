<?php
namespace app\admin\controller;

use app\common\model\User as UserModel;
use app\common\controller\AdminBase;
use think\Config;
use think\Db;

/**
 * 用户管理
 * Class AdminUser
 * @package app\admin\controller
 */
class User extends AdminBase
{
    protected $user_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->user_model = new UserModel();
      
    }

    /**
     * 用户管理
     * @param string $keyword
     * @param int    $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
        	session('userkeyword',$keyword);
        	  $map['username|mobile|usermail'] = ['like', "%{$keyword}%"];
        }else{
        
        	if(session('userkeyword')!=''&&$page>1){
        		$map['username|mobile|usermail'] = ['like', "%".session('userkeyword')."%"];
        	
        	}else{
        		session('userkeyword',null);
        	}  
        }
       
        $user_list = $this->user_model->where($map)->order('id DESC')->paginate(10);
        return $this->fetch('index', ['user_list' => $user_list, 'keyword' => $keyword]);
    }

    public function toggle($id, $status, $name)
    {
        if ($this->request->isGet()) {

            if ($this->user_model->where('id', $id)->update([$name => $status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }
    /**
     * 添加用户
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存用户
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'User');

            if ($validate_result !== true) {
               // $this->error($validate_result);
                return json(array('code' => 0, 'msg' =>$validate_result));
            } else {
            	
            	$data['salt'] = generate_password(18);
                $data['password'] = md5($data['password'] . $data['salt']);
                if ($this->user_model->allowField(true)->save($data)) {
                    //$this->success('保存成功');
                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                   // $this->error('保存失败');
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
        }
    }

    /**
     * 编辑用户
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $user = $this->user_model->find($id);

        return $this->fetch('edit', ['user' => $user]);
    }

    /**
     * 更新用户
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'User');

            if ($validate_result !== true) {
              //  $this->error($validate_result);
               return json(array('code' => 0, 'msg' => $validate_result));
            } else {
                $user           = $this->user_model->find($id);
                $user->id       = $id;
                $user->username = $data['username'];
                $user->mobile   = $data['mobile'];
                $user->usermail    = $data['email'];
                
                if($data['status']==0&&$user['status']>0){
                	$user->status   = 0-$user['status'];//等于 负的状态，当恢复时可以变为正数
                }else if($data['status']==0&&$user['status']<=0){
                	
                	//不变
                }else if($data['status']==1&&$user['status']>0){
                	//不变
                }else {
                	$user->status   = 0-$user['status'];
                }
                
               
                $user->point   = $data['point'];
                
                if (!empty($data['password']) && !empty($data['confirm_password'])) {
                    $user->password = md5($data['password'] . $user['salt']);
                }
                if ($user->save() !== false) {
                   // $this->success('更新成功');
                    return json(array('code' => 200, 'msg' => '更新成功'));
                } else {
                   // $this->error('更新失败');
                    return json(array('code' => 0, 'msg' => '更新失败'));
                }
            }
        }
    }

    /**
     * 删除用户
     * @param $id
     */
    public function delete($id)
    {
        if ($this->user_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
           // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
}