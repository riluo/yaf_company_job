#! /usr/bin/python
# coding: utf-8
# 计划任务: 删除图片缓存目录中超过三天未修改的文件, 每天执行

import os
import time

def clearFile(dir, expire):
    if not os.path.isdir(dir):
        return
    files = map(lambda f: os.path.join(dir, f),
        filter(lambda f: os.path.isfile(os.path.join(dir, f)), os.listdir(dir)))
    for file in files:
        if time.time() - os.path.getmtime(file) >= expire:
            print(file)
            os.remove(file)

if __name__ == '__main__':
    clearFile('/tmp/imageCache/', 3600 * 24 * 3)