<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\Book;
use ZuoYeah\Service\BookService;

class BookSpider
{
    /**
     * @var string
     */
    protected static $urlWithoutToken = 'http://api.knowbox.cn/v1_tiku/textbook/teachingassist?source=androidTeacher&version=1300&channel=Umeng&textbook_id=0&teaching_id=0&token=';
    /**
     * @var string
     */
    protected $token = '';

    protected $subject = '';
    protected $continueBookId = 0;
    protected $continueContentId = 0;

    /**
     * @var BookService
     */
    protected $bookService;

    function __construct($subject, $token, $continueBookId = 0, $continueContentId = 0)
    {
        $this->token = $token;
        $this->subject = $subject;
        $this->continueBookId = $continueBookId;
        $this->continueContentId = $continueContentId;

        $this->bookService = new BookService();
    }

    function gather()
    {
        $data = file_get_contents(self::$urlWithoutToken . $this->token);
        $json = json_decode($data, true);
        $list = $json['data'];
        $this->process($list);
    }

    protected function process(array $items)
    {
        foreach ($items as $item) {

            echo  date('H:i:s ') . '开始处理：' . $item['teachingAssistName'] . PHP_EOL;
            $origin = $item['teachingAssistID'] . '@knowbox';

            $book = $this->bookService->findByOrigin($origin);
            if (empty($book)) {
                $book = new Book();
                $book->title = $item['teachingAssistName'];
                $book->version = $item['teachingName'];
                $book->cover = '1';
                $book->type = Book::TYPE_EXERCISE;
                $book->pubDate = new \DateTime();
                $book->subject = $this->subject;
                $book->origin = $origin;

                $this->bookService->create($book);
            } else if ($book->id < $this->continueBookId) {
                continue;
            }


            $qs = new BookContentSpider(
                $this->subject,
                $this->token,
                $book->id,
                $this->continueContentId
            );
            $qs->gather($item['teachingAssistID']);

        }

        echo  date('H:i:s ') . '处理完毕'. PHP_EOL;
    }
}