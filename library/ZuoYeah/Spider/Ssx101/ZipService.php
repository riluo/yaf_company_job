<?php
namespace ZuoYeah\Spider\Ssx101;

use Gram\Utility\Helper\StringHelper;

class ZipService
{
    function unzip($path)
    {
        exec(sprintf('unzip -d %s %s/res.zip', $path, $path));
    }

    function unzipAll($dir)
    {
        $list = scandir($dir);
        foreach ($list as $folder) {
            if ($folder == '.' || $folder == '..' || $folder == '.DS_Store') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $folder;
            $this->unzip($path);
        }
    }

    function rename($dir)
    {
        $list = scandir($dir);
        foreach ($list as $folder) {
            if ($folder == '.' || $folder == '..' || $folder == '.DS_Store') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $folder;
            exec(sprintf('rm -rf %s/%s', $path, $folder));
        }
    }
}