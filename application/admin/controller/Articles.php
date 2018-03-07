<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Article as ArticleModel;
use app\common\model\Articlecate as ArticlecateModel;
use think\Db;
class Articles extends AdminBase
{
    protected $article_model;
    protected function _initialize()
    {
        parent::_initialize();
        $this->article_model = new ArticleModel();
    }

    public function index($keyword = '', $page = 1)
    {

        $map = [];

        if ($keyword) {
            session('articlekeyword', $keyword);
            $map['title|f.keywords'] = ['like', "%{$keyword}%"];
        } else {

            if (session('articlekeyword') != '' && $page > 1) {
                $map['title|f.keywords'] = ['like', "%" . session('articlekeyword') . "%"];
            } else {
                session('articlekeyword', null);
            }

        }

        $article_list = $this->article_model->alias('f')->join('articlecate c', 'c.id=f.tid')->field('f.*,c.id as tid,c.name,c.template')->order('f.id desc')->where($map)->paginate(10);

        return $this->fetch('index', ['article_list' => $article_list, 'keyword' => $keyword]);
    }

    public function toggle($id, $status, $name)
    {
        if ($this->request->isGet()) {

            if ($this->article_model->where('id', $id)->update([$name => $status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }
    /**
     * 编辑分类
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $category = new ArticlecateModel();

        $tptcs = $category->catetree();

      
        $this->assign(array('tptcs' => $tptcs));
        $tptc = $this->article_model->find($id);

        return $this->fetch('edit', ['tptca' => $tptc]);
       //return view();
    }

    /**
     * 更新分类
     * @throws \think\Exception
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
         //   $data['content'] = remove_xss($data['content']);
         //   $data['title'] = $data['title'];
       
            // 过滤post数组中的非数据表字段数据
            $res=$this->article_model->allowField(true)->save($data,['id' => $data['id']]);
            if ($res) {
                if (!empty($data['linkinfo'])) {
                    $data['linkinfo'] = remove_xss($data['linkinfo']);
                    if (!empty($data['score'])) {
                        $data['score']=0;$data['otherinfo']='';
                    }
                    $res = hook('attachlinksave', array('score' => $data['attachscore'], 'linkinfo' => $data['linkinfo'],'otherinfo' => $data['otherinfo'], 'id' => $data['id'], 'edit' => 1, 'type' => 1));
                }
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }
    }

    /**
     * 删除分类
     * @param $id
     * @throws \think\Exception
     */
    public function delete($id)
    {
        $info = $this->article_model->find($id);
        $score = getpoint($info['uid'], 'articleadd', $id);
        point_note(0 - $score, $info['uid'], 'articledelete', $id);

        if ($this->article_model->destroy($id)) {

            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    public function alldelete()
    {
        $params = input('post.');
        foreach ($params['ids'] as $k => $v) {
            $info = $this->article_model->find($v);
            $score = getpoint($info['uid'], 'articleadd', $v);
            point_note(0 - $score, $info['uid'], 'articledelete', $v);

        }

        $ids = implode(',', $params['ids']);
        $result = $this->article_model->destroy($ids);
        if ($result) {
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
}
