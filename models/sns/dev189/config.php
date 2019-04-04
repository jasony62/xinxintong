<?php
namespace sns\dev189;

/**
 * 获取能力开放平台accessToken的接口地址
 */
!defined('DEV_GET_ACCESSTOKEN_URL') && define('DEV_GET_ACCESSTOKEN_URL', 'http://api.developer.189.cn/api/token');
/**
 * 能力开放平台登录页面地址
 */
!defined('DEV_AUTH_LOGIN_URL') && define('DEV_AUTH_LOGIN_URL', 'http://42.99.2.53/service/serviceLogin');
/**
 * 通过code获取能力开放平台用户信息的接口地址
 */
!defined('DEV_AUTH_USERINFO_URL') && define('DEV_AUTH_USERINFO_URL', 'http://42.99.2.53/service/serviceLogin/getUserInfo');