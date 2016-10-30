<?php
namespace ZuoYeah\Spider\Ssx101;

use GuzzleHttp\Client;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use ZuoYeah\Entity\Book;
use ZuoYeah\Entity\BookContent;
use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\Subject;

class BookService
{
    /**
     * @var string
     */
    protected static $urlTemplate = 'http://k12pocket.web.sdp.101.com/v0.2/subjects/{subject}/propertys/{property}/books?p_page=0&p_size=10000';

    /**
     * @param int $subject
     * @param int $property
     *
     * @return array
     */
    function findBooks($subject, $property)
    {
        $url = str_replace(
            '{property}',
            $property,
            str_replace(
                '{subject}',
                $subject,
                self::$urlTemplate
            )
        );

        $client = new Client();
        $response = $client->get($url)->getBody();
        $json = json_decode($response, true);
        if (isset($json['items'])) {
            return $json['items'];
        } else {
            return [];
        }
    }

    /**
     *
     */
    function findAllBooks()
    {
        $subjects = [
            Subject::BIOLOGY,
            Subject::CHEMISTRY,
            Subject::CHINESE,
            Subject::ENGLISH,
            Subject::GEOGRAPHY,
            Subject::HISTORY,
            Subject::MATHEMATICS,
            Subject::PHYSICS,
            Subject::POLITICS
        ];

        $properties = [
            Property::EXAM,
            Property::EXERCISE,
            Property::HANDBOOK,
            Property::RECITATION
        ];

        $items = [];
        foreach ($subjects as $subject) {
            foreach ($properties as $property) {
                $books = $this->findBooks($subject, $property);
                foreach ($books as $book) {
                    $items[] = $book;
                }
            }
        }
        return $items;
    }

    function saveBook($path, $id, $type)
    {
        $file = "{$path}/{$id}/book.tit";
        $str = file_get_contents($file);
//        {
//            "book_id":1,
//            "edition":null,
//            "version":1,
//            "remote_cover":"http://res.101.com/ssx/case/2014117/20140117150341191_0.jpg",
//            "book_name":"数学典型例题",
//            "subject_id":2,
//            "subject_name":"数学",
//            "brief":"数学典型例题",
//            "author":"18065150262",
//            "profile":null,
//            "stype":"人教版",
//            "downs":27227,
//            "price":0,
//            "questions":1116
//        }
        $json = json_decode($str, true);

        $book = new Book();
        $book->title = $json['book_name'];
        $book->version = $json['stype'];
        $book->cover = $this->uploadCover($path, $id);
        $book->type = $type;
        $book->pubDate = new \DateTime();
        $book->subject = $this->convertSubject($json['subject_id']);
        (new \ZuoYeah\Service\BookService())->create($book);
        return $book;
    }

    function saveContent($path, $folder, $bookId)
    {
        $parent = (new \ZuoYeah\Service\BookContentService())->findRoot($bookId);
        $items = $this->loadContent($path, $folder, $parent);
        return $items;
    }

    function loadQuestions($path, $folder, BookContent $content)
    {
        $id = array_shift(explode('/', $folder));
        $file = "{$path}/{$folder}.tit";
        if (!file_exists($file)) {
            return [];
        }
        $data = file_get_contents($file);
        $json = json_decode($data, true);
        foreach ($json['Q'] as &$q) {
            $prefix = 'question/' . uniqid();
            $q['N'] = $this->uploadImages($path, $id, $prefix, $q['N']);
            $q['E'] = $this->uploadImages($path, $id, $prefix, $q['E']);
            $q['A'] = $this->uploadImages($path, $id, $prefix, $q['A']);

            $question = new Question();

        }
        return $json['Q'];
    }

    function uploadImages($path, $id, $keyPreffix, $content)
    {
        $self = $this;
        $pattern = '/<img.*?src=[\'"](?P<src>.*?)[\'"].*?>/';
        return preg_replace_callback(
            $pattern,
            function ($matches) use ($path, $id, $keyPreffix, $self) {
                $fileName = pathinfo($matches['src'], PATHINFO_BASENAME);
                $file = "{$path}/{$id}/images/{$fileName}";
                if (file_exists($file)) {
                    $key = "{$keyPreffix}/{$fileName}";
                    $self->upload($file, $key);
                    return str_replace(
                        $matches['src'],
                        '/' . $key,
                        $matches[0]
                    );
                } else {
                    return '';
                }
            },
            $content
        );
    }

    function loadContent($path, $folder, BookContent $parent)
    {
        $id = array_pop(explode('/', $folder));
        $file = "{$path}/{$folder}/{$id}.clg";
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $items = json_decode($content, true);
        $index = 0;
        foreach ($items as &$item) {
            $content = new BookContent();
            $content->parentId = $parent->id;
            $content->title = $item['T'];
            $content->bookId = $parent->bookId;
            $content->depth = $parent->depth + 1;
            $content->isIndex = true;
            $content->orderId = $index++;
            (new \ZuoYeah\Service\BookContentService())->create($content);

            $item['children'] = $this->loadContent($path, $item['P'], $content);
            if (empty($item['children'])) {
                $item['questions'] = $this->loadQuestions($path, $item['P'], $content);
            } else {
                $item['questions'] = [];
            }
        }
        return $items;
    }

    protected function convertSubject($value)
    {
        switch ($value) {
            case 1:
                return Subject::CHINESE;
            case 2:
                return Subject::MATHEMATICS;
            case 5:
                return Subject::PHYSICS;
            case 4:
                return Subject::CHEMISTRY;
            case 11:
                return Subject::POLITICS;
            case 9:
                return Subject::HISTORY;
            case 10:
                return Subject::GEOGRAPHY;
            case 6:
                return Subject::BIOLOGY;
            case 3:
                return Subject::ENGLISH;
            default:
                throw new \Exception();
        }
    }

    protected function uploadCover($path, $id)
    {
        $key = 'book/' . uniqid() . '.png';
        $file = "{$path}/{$id}/res/icon.png";
        return $this->upload($file, $key);
    }

    protected function upload($file, $key)
    {
        $auth = new Auth(
            'MOTSFnMcfL17LP5WhFmDHKv3ieEwqaM_Mo0Kfp1i',
            'vGK4Ja9aQoKmnMpW-J7XtYqZufNvpCinfCsDw1RA'
        );
        $token = $auth->uploadToken('tiplus', null, 3600 * 24);
        $uploader = New UploadManager();
        list($ret, $err) = $uploader->putFile($token, $key, $file);
        if ($err !== null) {
            throw new \Exception($err);
        } else {
            return $ret['key'];
        }
    }
}