<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="renderer" content="webkit"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出错了!!</title>
    <style>
        body {
            color: #222;
            font: 16px/1.7 'Helvetica Neue', Helvetica, Arial, Sans-serif;
            background: #eff2f5;
        }

        a {
            text-decoration: none;
            color: #105cb6;
        }

        a:hover {
            text-decoration: underline;
        }

        .error {
            margin: 120px auto 0;
            width: 80%;
        }

        .error .header {
            overflow: hidden;
            font-size: 1.8em;
            line-height: 1.2;
            margin: 0 0 .33em .33em;
        }

        .error .header img {
            vertical-align: text-bottom;
        }

        .error .header .mute {
            color: #999;
            font-size: .5em;
        }

        .error hr {
            margin: 1.3em 0;
        }

        .error p, .error pre {
            margin: 0 0;
            color: #999;
        }

        .error p:last-child {
            margin-bottom: 0;
        }

        .error strong {
            font-size: 1.1em;
            color: #000;
        }

        .error .content {
            padding: 2em 1.25em;
            border: 1px solid #babbbc;
            border-radius: 5px;
            background: #f7f7f7;
            text-align: left;
        }
    </style>
</head>

<body>
<div class="page">
    <div class="error">
        <h1 class="header">
            <?php echo get_class($exception); ?>
        </h1>

        <div class="content">
            <p>类型：<?php echo get_class($exception); ?></p>

            <p>文件：<?php echo $exception->getFile(); ?></p>

            <p>行号：<?php echo $exception->getLine(); ?></p>
            <hr>
            <pre><?php echo $exception->getMessage(); ?></pre>
            <pre><?php echo $exception->getTraceAsString(); ?></pre>
        </div>
    </div>
</div>
<script src="http://static.zhihu.com/static/js/desktop/404.js"></script>
</body>
</html>
