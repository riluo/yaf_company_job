php.ini中yaf配置：

yaf.use_spl_autoload = 1
yaf.use_namespace = 1
yaf.cache_config = 1
yaf.environ = "development"

;yaf.library = ""
;yaf.action_prefer = 0
;yaf.lowcase_path = 0
;yaf.forward_limit = 5
;yaf.name_suffix = 1
;yaf.name_separator = ""

nginx配置：

server {
    listen       *:80;
    server_name  localhost;
    root         "D:/Visual-NMP-x64/www/Default/public";
    error_log    "D:/Visual-NMP-x64/logs/Nginx/DefaultWebSite-error.log";
    autoindex    on;
    index        index.php index.html index.htm;
    try_files $uri $uri/ /index.php$is_args$args;


    location ~ (^/phpmyadmin|^/sql\x20buddy|^/memcache|^/memadmin|^/phpredisadmin|^/webgrind|^/eaccelerator|^/rockmongo)/.+\.php$ { 
        allow 127.0.0.1;
        deny all;
        root         "D:/Visual-NMP-x64/www/Default/public";
        error_log    "D:/Visual-NMP-x64/logs/Nginx/DefaultWebSite-error.log";
        fastcgi_pass   127.0.0.1:9001;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO        $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
        include        fastcgi_params;
    }

    location ~ (^/phpmyadmin|^/sql\x20buddy|^/memcache|^/memadmin|^/phpredisadmin|^/webgrind|^/eaccelerator|^/rockmongo) {
        allow 127.0.0.1;
        deny all;
        root         "D:/Visual-NMP-x64/www/Default/public";
    error_log    "D:/Visual-NMP-x64/logs/Nginx/DefaultWebSite-error.log";
        autoindex on;           
    }                               

    error_page   500 502 503 504  /50x.html;
    location = /50x.html {
    error_log    "D:/Visual-NMP-x64/logs/Nginx/DefaultWebSite-error.log";
    }


    location  ~ [^/]\.php(/|$) {
            fastcgi_pass   127.0.0.1:9001;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
    }
}