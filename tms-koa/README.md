和`tms-koa`对接。

建立`auth/client.js`文件，提供用户认证。

调用`/auth/authorize`接口获得`token`

```
curl 'http://localhost:3000/auth/authorize' \
  -H 'Connection: keep-alive' \
  -H 'Pragma: no-cache' \
  -H 'Cache-Control: no-cache' \
  -H 'sec-ch-ua: "\\Not\"A;Brand";v="99", "Chromium";v="84", "Google Chrome";v="84"' \
  -H 'accept: application/json' \
  -H 'sec-ch-ua-mobile: ?0' \
  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.21 Safari/537.36' \
  -H 'Sec-Fetch-Site: same-origin' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: http://localhost:8000/rest/site/fe/matter/enroll?site=51855414326b5ff2b1b4a3b5d366393c&app=5edca8c994292&page=enroll' \
  -H 'Accept-Language: zh-CN,zh;q=0.9' \
  -H 'Cookie: io=m37opqLRKFqxkit_AAAB; PHPSESSID=5ed9c1f253003c88b42720785020c206; adminer_key=3afeefee49f603ad5bd8e0e8ef6b7184; xxt_site_user_login=Ed9vbtK1flXqSl60XWreRLc5izwRex7Yo0syKGN11eqRbM386s3tr5vnAyz0rqDLa7PkfOMeRtEhu8K_SuQsyspV9zx2Xi6UNjwauEkmEu54sfTaOSql9GmPJUnHkVOiym08NiHfMm8xpbqb8_EeQe7U; xxt_site_platform_fe_user=FdtmatW6elfqSl6zUCeKD-YigSxBfRyIpBs6fjFgwKqZJ4K18cLhp9z_G3fsoanVZ6OsRflIGZpqoN27FuJg0tdOqWAmBnbGaW4UoB4xGu9xoeWdLnHp4XeYfBOZnQ7Qi38zZmjfZDFj9eTrs7BGAbyLg0SNKYuOU7qHOiR3; xxt_site_51855414326b5ff2b1b4a3b5d366393c_fe_user=nP64Sl-815PpYOZmdLZw7Rt6RDWz6-gVHhltsChlBi3O4T32TrS8-7Bmjr9NtJTtqbsa9ra6PGypP1cQDh-r3ic8Fuqexx6c2u5KhdtTHAIJEAy4ri7zyHpxn3ILZxckf-UmdLQj-eQI3MLxVIdzuJkMNX3-H_0NMKohgcbk; adminer_settings=; adminer_sid=9e6dc8bf081ae353f0a2863029f21a1b; adminer_permanent=c2VydmVy-ZGI%3D-cm9vdA%3D%3D-eHh0%3AIaeQLW8gQGXMp%2FB0'
```

# 文件服务（tms-finder）

文件服务需要进行访问控制。

请求中用`site`参数作为租户标识。

`config/fs.js`

`local.rootDir`指向文件目录（kcfinder/upload）

建立图片`domain`

`defaultDomain`为图片
