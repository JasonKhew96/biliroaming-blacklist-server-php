<!DOCTYPE html>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>哔哩哔哩解析服务器公用黑名单</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous" />
</head>

<body>
    <div class="container">
        <h1>开放接口</h1>
        <h2>检查黑白名单</h2>
        <h3>网站已接入 cloudflare, 申请接入请到 <a href="https://b23.ink/b7jJCl">Telegram@biliroaming_chat</a> 找管理员</h3>
        <h3>UID</h3>
        <p><code>https://black.qimo.ink/status.php?uid=114514</code></p>
        <h3>access key</h3>
        <p><code>https://black.qimo.ink/status.php?access_key=32150285b345c48aa3492f9212f61ca2</code></p>
        <h2>返回内容</h2>
        <p>
            <code>
                {"code":0,"message":"0","data":{"uid":114514,"is_blacklist":true,"is_whitelist":false,"reason":"臭"}}
            </code>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>