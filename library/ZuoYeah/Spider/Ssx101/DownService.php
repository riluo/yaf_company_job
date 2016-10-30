<?php
namespace ZuoYeah\Spider\Ssx101;

class DownService
{
    /**
     * @var string
     */
    protected static $zipUrlTemplate = 'http://store.service.101.com/res/bzip/1000/{bookId}.zip';

    /**
     * @param array  $books
     * @param string $path
     */
    function save(array $books, $path)
    {
        foreach ($books as $book) {
            $id = $book['id'];
            $dir = $path . '/' . $id;
            if (!file_exists($dir)) {
                mkdir($dir, 0777);
            }

            file_put_contents($dir . '/book.json', json_encode($book, JSON_UNESCAPED_UNICODE));
            exec(sprintf('wget %s -O %s',
                $book['icon_url'],
                $dir . '/avatar.' . pathinfo($book['icon_url'], PATHINFO_EXTENSION)
            ));
            exec(sprintf('wget %s -O %s',
                str_replace('{bookId}', $id, self::$zipUrlTemplate),
                $dir . '/res.zip'
            ));

            var_dump('down:' . $id);
        }
    }
}