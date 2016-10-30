<?php
namespace ZuoYeah\Command\DataFix;

use Gram\Gearman\GearmanFactory;
use Gram\Utility\Helper\StringHelper;
use Gram\Utility\Helper\ThrowHelper;
use ImagickDraw;
use ImagickPixel;
use PHPPdf\Core\Node\Barcode;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorJPG;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\PageInfo;
use ZuoYeah\Entity\Search\StudentSearch;
use ZuoYeah\Gearman\MessageWorker;
use ZuoYeah\Gearman\TaskStudentWorker;
use ZuoYeah\Service\StatService;
use ZuoYeah\Service\StudentService;
use ZuoYeah\Service\MarkBookService;
use ZuoYeah\Service\TaskService;

class MarkBookCommand extends Command
{
    private $horizontalConfig = '{
    "blockHeight":0.015,
    "barCodeHeight":0.15,
    "padding":{
        "top":0.06,
        "bottom":0.06,
        "outer":0.056,
        "inner":0.056
    }
}';

    private $verticalConfig = '{
    "blockHeight":0.01,
    "barCodeHeight":0.15,
    "padding":{
        "top":0.06,
        "bottom":0.06,
        "outer":0.1,
        "inner":0.13
    }
}
';

    public $bookPath = '';
    public $markedBookPath = '';
    public $processPath = '';
    public $pages = 0;
    public $bookId = 0;

    public $leftOfFirstIndexIsInner = true;
    public $firstPageIndex = 1;
    public $pageWidth = 0;
    public $pageHeight = 0;
    public $blockHeight = 0.05;
    public $blockWidth = 0;
    public $barCodeHeight = 0;
    public $barCodeWidth = 0;
    public $padding = [
        'top' => 0.062,
        'bottom' => 0.062,
        'outer' => 0.062,
        'inner' => 0.062,
    ];


    public $blockHeightPixel = 0;
    public $blockWidthPixel = 0;
    public $barCodeHeightPixel = 0;
    public $barCodeWidthPixel = 0;
    public $paddingPixel = [
        'top' => 0,
        'bottom' => 0,
        'outer' => 0,
        'inner' => 0,
    ];

    public $tmpConvertPath = '';
    public $tmpMarkPath = '';
    public $ignorePages = [];

    protected function configure()
    {
        $this->setName('datafix:markbook')
            ->setDescription('给书本做标记')
            ->addArgument(
                'processPath',
                InputArgument::REQUIRED,
                '配置文件的目录'
            )
            ->addArgument(
                'bookId',
                InputArgument::REQUIRED,
                '书本Id'
            )
            ->addArgument(
                'firstPageIndex',
                InputArgument::REQUIRED,
                '页码1在第几页'
            )
            ->addArgument(
                'ignorePages',
                InputArgument::OPTIONAL,
                '忽略页码'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processPath = $input->getArgument('processPath', '');
        ThrowHelper::ifFalse(is_dir($this->processPath), '处理路径不存在', ErrorCode::COMMON_NOT_EXIST);
        $this->bookId = $input->getArgument('bookId', 0);
        $this->firstPageIndex = $input->getArgument('firstPageIndex', 1);
        $this->tmpConvertPath = $this->processPath . $this->bookId . '/';
        $this->ignorePages = json_decode('[' . $input->getArgument('ignorePages', "") . ']', true);


        if (!is_dir($this->tmpConvertPath)) {
            echo Date('H:i:s ' . '开始转图片' . PHP_EOL);
            $this->convertToImage();
        }

        $this->countPage();

        echo Date('H:i:s ') . '做标记' . PHP_EOL;

        $this->loadConfig();

        $this->markBook();

        echo Date('H:i:s ') . '合并pdf' . PHP_EOL;
        $this->convertToPdf();

        echo Date('H:i:s ') . json_encode($this) . PHP_EOL;
    }

    private function convertToImage()
    {
        mkdir($this->tmpConvertPath);
        $cmd = "convert -density 300 '" . $this->bookPath . "' '" . $this->tmpConvertPath . "%d.jpg'";
        system($cmd);
        echo Date('H:i:s ') . '图片输出完成' . PHP_EOL;
    }


    private function markBook()
    {

        $this->tmpMarkPath = $this->tmpConvertPath . '/' . uniqid('mark_images_') . '/';
        echo Date('H:i:s ') . '创建临时目录:' . $this->tmpMarkPath . PHP_EOL;
        mkdir($this->tmpMarkPath);

        for ($page = $this->firstPageIndex; $page <= $this->pages; $page++) {
            if (in_array($page, $this->ignorePages)) {
                continue;
            }
            $this->markPage($page);
        }
    }


    private function convertToPdf()
    {


        $images = [];
        for ($page = 1; $page <= $this->pages; $page++) {
            $images[] = ($page < $this->firstPageIndex || in_array($page, $this->ignorePages))
                ? $this->getImagePath($page)
                : $this->getMarkImagePath($page);
        }

        echo Date('H:i:s ') . '使用系统命令合并' . PHP_EOL;

        $cmd = "convert  '" . implode("' '", $images) . "' '" . $this->markedBookPath . "'";
        system($cmd);

        if (file_exists($this->markedBookPath)) {
            return;
        }

        echo Date('H:i:s ') . '加载图片' . PHP_EOL;
        $pdf = new \Imagick($images);

//        $pdf = new \Imagick();
//        for ($page = 1; $page <= $this->pages; $page++) {
//            echo Date('H:i:s ') . '合并pdf' . $page . PHP_EOL;
//            $image = new \Imagick($page < $this->firstPageIndex
//                ? $this->getImagePath($page)
//                : $this->getMarkImagePath($page));
//            $pdf->addImage($image);
//        }
        echo Date('H:i:s ') . '设置pdf格式' . PHP_EOL;

        $pdf->setImageFormat('pdf');
        echo Date('H:i:s ') . '保存pdf' . PHP_EOL;
        $pdf->writeImages($this->markedBookPath, true);
    }

    private function markPage($page)
    {
        $imagePage = $this->getImagePath($page);
        $image = new \Imagick($imagePage);

        $leftIsInner = $this->leftIsInner($page);
        echo Date('H:i:s ' . '标点 ' . $page . PHP_EOL);

        $this->markBlock($image, $leftIsInner);

        echo Date('H:i:s ' . '添加条码' . $page . PHP_EOL);
        $this->markPageInfo($image, $page, $leftIsInner);

        echo Date('H:i:s ' . '保存图片' . $page . PHP_EOL);
        $image->writeImage($this->getMarkImagePath($page));

    }

    private function getImagePath($page)
    {
        return $this->tmpConvertPath . ($page - 1) . '.jpg';
    }


    private function getMarkImagePath($page)
    {
        return $this->tmpMarkPath . ($page - 1) . '.jpg';
    }

    private function loadConfig()
    {

        $imagePage = $this->getImagePath(1);
        $image = new \Imagick($imagePage);

        if ($this->pageWidth == 0) {
            $this->pageWidth = $image->getImageWidth();
            $this->pageHeight = $image->getImageHeight();
        }


        $configPath = $this->processPath . $this->bookId . '.json';

        if (file_exists($configPath)) {
            echo Date('H:i:s ') . '使用外部配置' . PHP_EOL;

            $config = file_get_contents($configPath);
        } else {
            echo Date('H:i:s ') . '使用默认配置' . PHP_EOL;

            $config = $this->pageWidth > $this->pageHeight ? $this->horizontalConfig : $this->verticalConfig;
        }

        $config = json_decode($config, true);


        if ($config) {
            foreach ($config as $key => $value) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }

        if ($this->paddingPixel['top'] == 0) {
            $this->calPaddingPixel();
        }

        ThrowHelper::ifEmpty($this->bookId, '书本Id不能为空', ErrorCode::COMMON_NOT_EXIST);

        $this->bookPath = $this->processPath . $this->bookId . '.pdf';
        $this->markedBookPath = $this->processPath . $this->bookId . '_marked.pdf';

    }

    /**
     * @param $image \Imagick
     * @param $leftIsInner
     */
    private function markBlock($image, $leftIsInner)
    {
        $leftPadding = $leftIsInner ? $this->paddingPixel['inner'] : $this->paddingPixel['outer'];
        $rightPadding = $leftIsInner ? $this->paddingPixel['outer'] : $this->paddingPixel['inner'];

        echo Date('H:i:s ') . '标点' . 'top left' . PHP_EOL;
        //top left
        $this->drawImage($image,
            $this->paddingPixel['top'] - $this->blockHeightPixel,
            $leftPadding - $this->barCodeWidthPixel);

        echo Date('H:i:s ') . '标点' . 'top right' . PHP_EOL;
        //top right
        $this->drawImage($image,
            $this->paddingPixel['top'] - $this->blockHeightPixel,
            $this->pageWidth - $rightPadding - $this->blockWidthPixel + $this->barCodeWidthPixel);


        echo Date('H:i:s ') . '标点' . 'middle left' . PHP_EOL;
        //middle left
        $this->drawImage($image,
            intval(($this->pageHeight - $this->paddingPixel['bottom'] + $this->paddingPixel['top'] - $this->blockHeightPixel) / 2),
            $leftPadding - $this->barCodeWidthPixel,
            true);

        echo Date('H:i:s ') . '标点' . 'bottom right' . PHP_EOL;
        //bottom right
        $this->drawImage($image,
            $this->pageHeight - $this->paddingPixel['bottom'],
            $this->pageWidth - $rightPadding - $this->blockWidthPixel + $this->barCodeWidthPixel);


        echo Date('H:i:s ') . '标点' . 'bottom left' . PHP_EOL;
        //bottom left
        $this->drawImage($image,
            $this->pageHeight - $this->paddingPixel['bottom'],
            $leftPadding - $this->barCodeWidthPixel);

        echo Date('H:i:s ') . '标点' . 'middle right' . PHP_EOL;
        //middle right
        $this->drawImage($image,
            intval(($this->pageHeight - $this->paddingPixel['bottom'] + $this->paddingPixel['top'] - $this->blockHeightPixel) / 2),
            $this->pageWidth - $rightPadding,
            true);


        if ($this->pageWidth > $this->pageHeight) {

            echo Date('H:i:s ') . '标点' . 'top middle' . PHP_EOL;
            //top middle
            $this->drawImage($image,
                $this->paddingPixel['top'] - $this->blockHeightPixel,
                intval(($this->pageWidth - $rightPadding + $leftPadding - $this->blockWidthPixel) / 2)
            );

            echo Date('H:i:s ') . '标点' . 'top middle left' . PHP_EOL;
            //top middle left
            $this->drawImage($image,
                $this->paddingPixel['top'] - $this->blockHeightPixel,
                intval(($this->pageWidth - $rightPadding + 3 * $leftPadding
                        - $this->blockWidthPixel - 2 * $this->barCodeWidthPixel) / 4),
                true);


            //top middle right
            $this->drawImage($image,
                $this->paddingPixel['top'] - $this->blockHeightPixel,
                intval(((3 * $this->pageWidth - 3 * $rightPadding
                        + 2 * $this->barCodeWidthPixel
                        + $leftPadding + $this->blockWidthPixel) / 4)
                ),
                true);

            echo Date('H:i:s ') . '标点' . 'bottom middle' . PHP_EOL;
            //bottom middle
            $this->drawImage($image,
                $this->pageHeight - $this->paddingPixel['bottom'],
                intval(($this->pageWidth - $rightPadding + $leftPadding - $this->blockWidthPixel) / 2)
            );

            echo Date('H:i:s ') . '标点' . 'bottom middle left' . PHP_EOL;
            //bottom middle left
            $this->drawImage($image,
                $this->pageHeight - $this->paddingPixel['bottom'],
                intval(($this->pageWidth - $rightPadding + 3 * $leftPadding
                        - $this->blockWidthPixel - 2 * $this->barCodeWidthPixel) / 4),
                true);


            //bottom middle right
            $this->drawImage($image,
                $this->pageHeight - $this->paddingPixel['bottom'],
                intval(((3 * $this->pageWidth - 3 * $rightPadding
                        + 2 * $this->barCodeWidthPixel
                        + $leftPadding + $this->blockWidthPixel) / 4)
                ),
                true);

        } else {
            echo Date('H:i:s ') . '标点' . 'top middle' . PHP_EOL;
            //top middle
            $this->drawImage($image,
                $this->paddingPixel['top'] - $this->blockHeightPixel,
                intval(($this->pageWidth - $this->blockHeightPixel) / 2),
                true);

            echo Date('H:i:s ') . '标点' . 'bottom middle' . PHP_EOL;
            //bottom middle
            $this->drawImage($image,
                $this->pageHeight - $this->paddingPixel['bottom'],
                intval(($this->pageWidth - $this->blockHeightPixel) / 2),
                true);

        }
    }

    /**
     * @param $image \Imagick
     * @param $top
     * @param $left
     * @param bool $isMiddle
     */
    private function drawImage($image, $top, $left, $isMiddle = false)
    {
        $dw = new ImagickDraw();
        $dw->setFillColor(new ImagickPixel('black'));
        $dw->rectangle($left, $top,
            $left + ($isMiddle ? $this->blockHeightPixel : $this->blockWidthPixel),
            $top + $this->blockHeightPixel);
        $image->drawImage($dw);
    }

    private function leftIsInner($page)
    {
        return !!($page % 2);
    }

    private function calPaddingPixel()
    {
        $this->paddingPixel['top'] = intval($this->pageHeight * $this->padding['top']);
        $this->paddingPixel['bottom'] = intval($this->pageHeight * $this->padding['bottom']);
        $this->paddingPixel['inner'] = intval($this->pageWidth * $this->padding['inner']);
        $this->paddingPixel['outer'] = intval($this->pageWidth * $this->padding['outer']);
        $this->blockHeightPixel = intval($this->pageHeight * $this->blockHeight);
        $this->blockWidthPixel = intval($this->pageWidth * $this->blockWidth);
        $this->barCodeHeightPixel = intval($this->barCodeHeight * $this->pageWidth);
        $this->barCodeWidthPixel = intval($this->barCodeWidth * $this->pageHeight);


        if (empty($this->barCodeWidthPixel)) {
            $this->barCodeWidthPixel = $this->blockHeightPixel;
        }

        if (empty($this->blockWidthPixel)) {
            $this->blockWidthPixel = 2 * $this->blockHeightPixel;
        }
    }

    /**
     * @param $image \Imagick
     * @param $page
     */
    private function markPageInfo($image, $page, $leftIsInner)
    {

        $pageInfo = new PageInfo($this->bookId, $page - $this->firstPageIndex + 1);
        $code = $pageInfo->toBarCode(1);

        echo Date('H:i:s ') . '生成条形码' . $code . PHP_EOL;

        $generatorPNG = new BarcodeGeneratorPNG();
        $imgData = $generatorPNG->getBarcode($code, $generatorPNG::TYPE_EAN_13, 2, 30);
        $barcode = new \Imagick();
        $barcode->readImageBlob($imgData);
        $barcode->rotateImage('transparent', 90);
        echo Date('H:i:s ') . '加条形码' . PHP_EOL;
        $dw = new ImagickDraw();

        $left_outer = $leftIsInner ? $this->pageWidth - $this->paddingPixel['outer']
            : $this->paddingPixel['outer'] - $this->barCodeWidthPixel;

        $left_inner = $leftIsInner ? $this->paddingPixel['inner'] - $this->barCodeWidthPixel
            : $this->pageWidth - $this->paddingPixel['inner'];

        $dw->composite($barcode->getImageCompose(),
            $left_outer,
            $this->paddingPixel['top'] + intval($this->barCodeWidthPixel / 2),
            $this->barCodeWidthPixel,
            $this->barCodeHeightPixel,
            $barcode);
        $image->drawImage($dw);

        if ($this->pageWidth > $this->pageHeight) {

            $dw->composite($barcode->getImageCompose(),
                $left_inner,
                $this->paddingPixel['top'] + intval($this->barCodeWidthPixel / 2),
                $this->barCodeWidthPixel,
                $this->barCodeHeightPixel,
                $barcode);
            $image->drawImage($dw);
        }

    }

    private function countPage()
    {
        if ($this->pages == 0) {
            echo Date('H:i:s ') . '获取pdf页码' . PHP_EOL;

            foreach (scandir($this->tmpConvertPath) as $file) {
                if (StringHelper::endWith($file, '.jpg')) {
                    $this->pages++;
                }
            }
        }
        echo Date('H:i:s ') . '共' . $this->pages . '页' . PHP_EOL;

    }

}