<?php
namespace app\user\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'username'         => 'require|unique:user|length:4,26',
        'password'         => 'confirm:confirm_password|length:6,16',
        'confirm_password' => 'confirm:password',
        'usermail'            => 'email|unique:user',
        'mobile'            => 'mobile|unique:user',
    ];
    protected $regex = [
      'mobile'    => '^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\\d{8}$',
      'password'  => '/^[\w]{6,15}$/'
    ];
    protected $message = [
        'username.require'         => '请输入用户名',
    		'username.length'         => '用户名在4位到26位之间',
        'username.unique'          => '用户名已存在',
    		'usermail.unique'          => '邮箱已存在',
        'password.confirm'         => '两次输入密码不一致',
    		'password.length'         => '密码在6位到16位之间',
        'confirm_password.confirm' => '两次输入密码不一致',
        'usermail.email'              => '邮箱格式错误',
        'mobile.mobile'              => '手机号格式不正确',
        'mobile.unique'          => '手机号已存在',
    ];
    
    protected $scene = [
    		'passwordedit'  =>  ['password','confirm_password'],
    ];
    
}