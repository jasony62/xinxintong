<?php
use PhpOffice\PhpPresentation\AbstractShape;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Drawing;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\RichText\BreakElement;
use PhpOffice\PhpPresentation\Shape\RichText\TextElement;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Style\Color;

/*
 * pptx转单图文
 * author lintao
 * date 2017,3rd of May
 */
class pptx_to_article
{
    protected $oPhpPresentation;
    public $title;
    public $creator;
    public $crate_at;
    public $modify_at;
    public $subject;
    public $htmlOutput;

    public function __construct(PhpPresentation $oPHPPpt)
    {
        $this->oPhpPresentation = $oPHPPpt;
        $this->displayPhpPresentationInfo($oPHPPpt);
    }

    protected function append($sHTML)
    {
        $this->htmlOutput .= $sHTML;
    }

    protected function displayPhpPresentation(PhpPresentation $oPHPPpt)
    {
        $this->append('<li><span><i class="fa fa-folder-open"></i> PhpPresentation</span>');
        $this->append('<ul>');
        $this->append('<li><span class="shape" id="divPhpPresentation"><i class="fa fa-info-circle"></i> Info "PhpPresentation"</span></li>');
        foreach ($oPHPPpt->getAllSlides() as $oSlide) {
            $this->append('<li><span><i class="fa fa-minus-square"></i> Slide</span>');
            $this->append('<ul>');
            $this->append('<li><span class="shape" id="div' . $oSlide->getHashCode() . '"><i class="fa fa-info-circle"></i> Info "Slide"</span></li>');
            foreach ($oSlide->getShapeCollection() as $oShape) {
                if ($oShape instanceof Group) {
                    $this->append('<li><span><i class="fa fa-minus-square"></i> Shape "Group"</span>');
                    $this->append('<ul>');
                    $this->append('<li><span class="shape" id="div' . $oShape->getHashCode() . '"><i class="fa fa-info-circle"></i> Info "Group"</span></li>');
                    foreach ($oShape->getShapeCollection() as $oShapeChild) {
                        $this->displayShape($oShapeChild);
                    }
                    $this->append('</ul>');
                    $this->append('</li>');
                } else {
                    $this->displayShape($oShape);
                }
            }
            $this->append('</ul>');
            $this->append('</li>');
        }
        $this->append('</ul>');
        $this->append('</li>');
    }

    protected function displayShape(AbstractShape $shape)
    {
        if ($shape instanceof Drawing\Gd) {
            $this->append('<li><span class="shape" id="div' . $shape->getHashCode() . '">Shape "Drawing\Gd"</span></li>');
        } elseif ($shape instanceof Drawing\File) {
            $this->append('<li><span class="shape" id="div' . $shape->getHashCode() . '">Shape "Drawing\File"</span></li>');
        } elseif ($shape instanceof Drawing\Base64) {
            $this->append('<li><span class="shape" id="div' . $shape->getHashCode() . '">Shape "Drawing\Base64"</span></li>');
        } elseif ($shape instanceof Drawing\Zip) {
            $this->append('<li><span class="shape" id="div' . $shape->getHashCode() . '">Shape "Drawing\Zip"</span></li>');
        } elseif ($shape instanceof RichText) {
            $this->append('<li><span class="shape" id="div' . $shape->getHashCode() . '">Shape "RichText"</span></li>');
        } else {
            var_dump($shape);
        }
    }

    protected function displayPhpPresentationInfo(PhpPresentation $oPHPPpt)
    {
        $this->title     = $oPHPPpt->getDocumentProperties()->getTitle();
        $this->creator   = $oPHPPpt->getDocumentProperties()->getCreator();
        $this->create_at = $oPHPPpt->getDocumentProperties()->getCreated();
        $this->modify_at = $oPHPPpt->getDocumentProperties()->getModified();
        $this->subject   = $oPHPPpt->getDocumentProperties()->getSubject();

        foreach ($oPHPPpt->getAllSlides() as $oSlide) {

            $this->append('<div class="infoBlk" id="div' . $oSlide->getHashCode() . 'Info">');
            $this->append('<dl>');

            //背景和图片
            $oBkg = $oSlide->getBackground();
            if ($oBkg instanceof Slide\AbstractBackground) {
                if ($oBkg instanceof Slide\Background\Color) {
                    $this->append('<dt>Background Color</dt><dd>#' . $oBkg->getColor()->getRGB() . '</dd>');
                }
                if ($oBkg instanceof Slide\Background\Image) {
                    $sBkgImgContents = file_get_contents($oBkg->getPath());
                    $this->append('<dt>Background Image</dt><dd><img src=data:image/png;base64,' . base64_encode($sBkgImgContents) . '"></dd>');
                }
            }
            //文本
            $oNote = $oSlide->getNote();
            if ($oNote->getShapeCollection()->count() > 0) {
                $this->append('<dt>Notes</dt>');
                foreach ($oNote->getShapeCollection() as $oShape) {
                    if ($oShape instanceof RichText) {
                        $this->append('<dd>' . $oShape->getPlainText() . '</dd>');
                    }
                }
            }

            $this->append('</dl>');
            $this->append('</div>');

            foreach ($oSlide->getShapeCollection() as $oShape) {
                if ($oShape instanceof Group) {
                    foreach ($oShape->getShapeCollection() as $oShapeChild) {
                        $this->displayShapeInfo($oShapeChild);
                    }
                } else {
                    $this->displayShapeInfo($oShape);
                }
            }
        }
    }

    protected function displayShapeInfo(AbstractShape $oShape)
    {

        $this->append('<div class="infoBlk" id="div' . $oShape->getHashCode() . 'Info">');
        $this->append('<dl>');

        if ($oShape instanceof Drawing\Gd) {
            // $this->append('<dt>Name</dt><dd>'.$oShape->getName().'</dd>');
            // $this->append('<dt>Description</dt><dd>'.$oShape->getDescription().'</dd>');
            ob_start();
            call_user_func($oShape->getRenderingFunction(), $oShape->getImageResource());
            $sShapeImgContents = ob_get_contents();
            ob_end_clean();
            // $this->append('<dt>Mime-Type</dt><dd>'.$oShape->getMimeType().'</dd>');
            $this->append('<p><img src="data:' . $oShape->getMimeType() . ';base64,' . base64_encode($sShapeImgContents) . '"></p>');
        } elseif ($oShape instanceof Drawing) {
            //  $this->append('<dt>Name</dt><dd>'.$oShape->getName().'</dd>');
            // $this->append('<dt>Description</dt><dd>'.$oShape->getDescription().'</dd>');
        } elseif ($oShape instanceof RichText) {
            foreach ($oShape->getParagraphs() as $oParagraph) {
                foreach ($oParagraph->getRichTextElements() as $oRichText) {
                    if ($oRichText instanceof BreakElement) {
                        $this->append('<br/>');
                    } else {
                        $link_text = $oRichText->getText();
                        if ($oRichText instanceof TextElement) {
                            //$this->append('<dt><i>TextElement</i></dt>');
                            if ($oRichText->hasHyperlink()) {
                                $link_text = '<p><a href=' . $oRichText->getHyperlink()->getUrl() . '>' . $oRichText->getText() . '</a></p>';
                            }
                        } else {
                            $this->append('<dt><i>Run</i></dt>');
                        }

                        $extra = ";";
                        $extra .= $oRichText->getFont()->isBold() ? "font-weight:bold;" : "";

                        $this->append("<p style=font-family:" . $oRichText->getFont()->getName() . ";font-size:" . $oRichText->getFont()->getSize() . ";font-color:#" . $oRichText->getFont()->getColor()->getARGB() . $extra . ">" . $link_text . "</p>");
                        $this->append('</dl>');
                        $this->append('</dd>');
                    }
                }
                $this->append('</dl></dd></dl>');
            }
            $this->append('</dd>');
        } else {
            // Add another shape
        }
        $this->append('</dl>');
        $this->append('</div>');
    }

    protected function getConstantName($class, $search, $startWith = '')
    {
        $fooClass  = new ReflectionClass($class);
        $constants = $fooClass->getConstants();
        $constName = null;
        foreach ($constants as $key => $value) {
            if ($value == $search) {
                if (empty($startWith) || (!empty($startWith) && strpos($key, $startWith) === 0)) {
                    $constName = $key;
                }
                break;
            }
        }
        return $constName;
    }
}
