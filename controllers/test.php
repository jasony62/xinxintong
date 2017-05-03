<?php
require_once dirname(__FILE__) . '/xxt_base.php';
require_once TMS_APP_DIR . '/vendor/autoload.php';
require_once TMS_APP_DIR.'/lib/pptx_to_article.php';

use PhpOffice\PhpPresentation\Autoloader;
use PhpOffice\PhpPresentation\Settings;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\AbstractShape;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\Shape\Drawing;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\RichText\BreakElement;
use PhpOffice\PhpPresentation\Shape\RichText\TextElement;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;
/**
 *
 */
class test extends xxt_base
{

    public function ppt_action()
    {
        // with Composer

        $objPHPPowerPoint = new PhpPresentation();

// Create slide
        $currentSlide = $objPHPPowerPoint->getActiveSlide();

// Create a shape (drawing)
        $shape = $currentSlide->createDrawingShape();
        $shape->setName('PHPPresentation logo')
            ->setDescription('PHPPresentation logo')
            ->setPath('cus/21.jpg')
            ->setHeight(36)
            ->setOffsetX(10)
            ->setOffsetY(10);
        $shape->getShadow()->setVisible(true)
            ->setDirection(45)
            ->setDistance(10);

// Create a shape (text)
        $shape = $currentSlide->createRichTextShape()
            ->setHeight(300)
            ->setWidth(600)
            ->setOffsetX(170)
            ->setOffsetY(180);
        $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $textRun = $shape->createTextRun('Thank you for using PHPPresentation!');
        $textRun->getFont()->setBold(true)
            ->setSize(60)
            ->setColor(new Color('FFE06B20'));

        //$another = $objPHPPowerPoint->createSlide();
        //$shape2  = $another->createDrawingShape();
        /*
        $shape2 = $another->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(170)
        ->setOffsetY(180);
        $shape2->createTextRun('2Thank you for using PHPPresentation!')
        ->getFont()
        ->setBold(true)
        ->setSize(60)
        ->setColor(new Color('FFE06B20'));
         */
        $oWriterPPTX = IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        $oWriterPPTX->save("sample.pptx");

        die('ok');
    }

    public function read_action($site)
    {

        $oReader = IOFactory::createReader('PowerPoint2007');
        $oPHPPresentation=$oReader->load('sample.pptx');

        $article = new pptx_to_article($oPHPPresentation);
        $model=$this->model();
        $d['creater_name']=$article->creator;
        $d['author']=$article->creator;
        $d['create_at']=$article->create_at;
        $d['title']=$article->title;
        $d['modify_at']=$article->modify_at;
        $d['body']=$article->htmlOutput;
        $d['siteid']=$site;
        $d['mpid']=$site;
        //var_dump($d['body']);die();
        $rst=$model->insert('xxt_article',$d,true);

        return new \responseData($rst);
    }
}
