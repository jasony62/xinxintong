#! /usr/bin/python

from pathlib import Path
import re
import shutil

counter = 0


def handleDir(dir):
    global counter
    if dir.is_dir():
        # 复制图片目录
        src = './kcfinder/upload/' + dir.name + '/图片'
        if Path(src).exists():
            dst = './kcfinder/upload/图片/' + dir.name
            if not Path(dst).exists():
                shutil.copytree(src, dst)
                print(dst)
            # 复制thumb目录
            srcThumbs = './kcfinder/upload/' + dir.name + '/_thumbs'
            if Path(srcThumbs).exists():
                dstThumbs = './kcfinder/upload/图片/_thumbs/' + dir.name
                if not Path(dstThumbs).exists():
                    shutil.copytree(srcThumbs, dstThumbs)
                    print(dstThumbs)
    counter += 1
    print('---- {} {}'.format(counter, dir.name))


p = Path('./kcfinder/upload')
for dir in p.iterdir():
    if re.match(r'\S{32}', dir.name):
        handleDir(dir)
