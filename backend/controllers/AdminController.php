<?php

namespace backend\controllers;
use common\models\User;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\widgets\LinkPager;

class AdminController extends BaseController
{
    public function actionIndex()
    {
        $keyword = Yii::$app->request->post('keyword','') ? Yii::$app->request->post('keyword','') : Yii::$app->request->get('keyword','');
        $data = User::find()->filterWhere(['or',['like','username',"$keyword"],['like','email',"$keyword"]])
            ->orderBy(['role'=>SORT_ASC,'created_at'=>SORT_DESC]);
        $pages = new Pagination(['totalCount' => $data->count(), 'pageSize' => 10]);
        $_GET['keyword'] = $keyword;
        $result = $data->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        $this->assign([
            "result" => $result,
            'pages' => LinkPager::widget(['pagination' => $pages]),
            'num' => (($pages->getPage() ? $pages->getPage() : 1) - 1 ) * $pages->getPageSize() + 1,
            'keyword' => $keyword,
        ]);
        return $this->display('index.html');
    }

    public function actionAdd()
    {
        $post = Yii::$app->request->post();
        if($post){
            $data = new User();
            if(empty($post['username'])){
                ajaxReturn(0,'用户名不能为空');
            }
            $checkUsername = $data->findByUsername($post['username']);
            if($checkUsername){
                ajaxReturn(0,'用户名已存在');
            }
            if(empty($post['email'])){
                ajaxReturn(0,'邮箱不能为空');
            }
            if(empty($post['password'])){
                ajaxReturn(0,'密码不能为空');
            }
            if(empty($post['qrPwd'])){
                ajaxReturn(0,'确认密码不能为空');
            }
            if($post['password'] !== $post['qrPwd']){
                ajaxReturn(0,'俩次输入密码不一致');
            }
            $data->username = $post['username'];
            $data->setPassword($post['password']);
            $data->email = $post['email'];
            $data->generateAuthKey();
            $begin = $data->getDb()->beginTransaction();//开启事物
            try{
                $data->save();
                if($data->primaryKey){//保存成功后续添加角色
                    $admin = Yii::$app->authManager->createRole('admin_'.$data->id);//创建角色
                    Yii::$app->authManager->add($admin);
                    Yii::$app->authManager->assign($admin,$data->id);//为用户分配角色
                }
                $begin->commit();
                ajaxReturn(1,'添加成功');
            }catch (Exception $e){
                $begin->rollBack();
                ajaxReturn(0,'添加失败');
            }
        }
        return $this->display('add.html');
    }

    public function actionUp()
    {
        return $this->display('index.html');
    }

    public function actionDel()
    {
        return $this->display('index.html');
    }

}
