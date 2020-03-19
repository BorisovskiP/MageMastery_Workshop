<?php

/**
 * Magic Slideshow view block
 *
 */
namespace MagicToolbox\MagicSlideshow\Block\Product\View;

use Magento\Framework\Data\Collection;
use MagicToolbox\MagicSlideshow\Helper\Data;

class Gallery extends \Magento\Catalog\Block\Product\View\Gallery
{
    /**
     * Helper
     *
     * @var \MagicToolbox\MagicSlideshow\Helper\Data
     */
    public $magicToolboxHelper = null;

    /**
     * MagicSlideshow module core class
     *
     * @var \MagicToolbox\MagicSlideshow\Classes\MagicSlideshowModuleCoreClass
     */
    public $toolObj = null;

    /**
     * Rendered gallery HTML
     *
     * @var array
     */
    protected $renderedGalleryHtml = [];

    /**
     * ID of the current product
     *
     * @var integer
     */
    protected $currentProductId = null;

    /**
     * Do reload product
     *
     * @var bool
     */
    protected $doReloadProduct = false;

    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->magicToolboxHelper = $objectManager->get(\MagicToolbox\MagicSlideshow\Helper\Data::class);
        $this->toolObj = $this->magicToolboxHelper->getToolObj();

        $version = $this->magicToolboxHelper->getMagentoVersion();
        if (version_compare($version, '2.2.5', '<')) {
            $this->doReloadProduct = true;
        }

        //NOTE: for versions 2.2.x (x >=9), 2.3.x (x >=2)
        if (class_exists('\Magento\Catalog\Block\Product\View\GalleryOptions')) {
            $galleryOptions = $objectManager->get(\Magento\Catalog\Block\Product\View\GalleryOptions::class);
            $this->setData('gallery_options', $galleryOptions);
        }

        //NOTE: for versions 2.3.x (x >=2)
        if (version_compare($version, '2.3.2', '>=')) {
            $imageHelper = $objectManager->get(\Magento\Catalog\Helper\Image::class);
            $this->setData('imageHelper', $imageHelper);
        }
    }

    /**
     * Retrieve collection of gallery images
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return Magento\Framework\Data\Collection
     */
    public function getGalleryImagesCollection($product = null)
    {
        static $images = [];
        if (is_null($product)) {
            $product = $this->getProduct();
        }
        $id = $product->getId();
        if (!isset($images[$id])) {
            if ($this->doReloadProduct) {
                $productRepository = \Magento\Framework\App\ObjectManager::getInstance()->get(
                    \Magento\Catalog\Model\ProductRepository::class
                );
                $product = $productRepository->getById($product->getId());
            }

            $images[$id] = $product->getMediaGalleryImages();
            if ($images[$id] instanceof \Magento\Framework\Data\Collection) {
                $baseMediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $baseStaticUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
                foreach ($images[$id] as $image) {
                    /* @var \Magento\Framework\DataObject $image */

                    $mediaType = $image->getMediaType();
                    if ($mediaType != 'image' && $mediaType != 'external-video') {
                        continue;
                    }

                    $img = $this->_imageHelper->init($product, 'product_page_image_large', ['width' => null, 'height' => null])
                            ->setImageFile($image->getFile())
                            ->getUrl();

                    $iPath = $image->getPath();
                    if (!is_file($iPath)) {
                        if (strpos($img, $baseMediaUrl) === 0) {
                            $iPath = str_replace($baseMediaUrl, '', $img);
                            $iPath = $this->magicToolboxHelper->getMediaDirectory()->getAbsolutePath($iPath);
                        } else {
                            $iPath = str_replace($baseStaticUrl, '', $img);
                            $iPath = $this->magicToolboxHelper->getStaticDirectory()->getAbsolutePath($iPath);
                        }
                    }
                    try {
                        $originalSizeArray = getimagesize($iPath);
                    } catch (\Exception $exception) {
                        $originalSizeArray = [0, 0];
                    }

                    if ($mediaType == 'image') {
                        if ($this->toolObj->params->checkValue('square-images', 'Yes')) {
                            $bigImageSize = ($originalSizeArray[0] > $originalSizeArray[1]) ? $originalSizeArray[0] : $originalSizeArray[1];
                            $img = $this->_imageHelper->init($product, 'product_page_image_large')
                                    ->setImageFile($image->getFile())
                                    ->resize($bigImageSize)
                                    ->getUrl();
                        }
                        $image->setData('large_image_url', $img);

                        list($w, $h) = $this->magicToolboxHelper->magicToolboxGetSizes('thumb', $originalSizeArray);
                        $medium = $this->_imageHelper->init($product, 'product_page_image_medium', ['width' => $w, 'height' => $h])
                                ->setImageFile($image->getFile())
                                ->getUrl();
                        $image->setData('medium_image_url', $medium);
                    }

                    list($w, $h) = $this->magicToolboxHelper->magicToolboxGetSizes('selector', $originalSizeArray);
                    $thumb = $this->_imageHelper->init($product, 'product_page_image_small', ['width' => $w, 'height' => $h])
                            ->setImageFile($image->getFile())
                            ->getUrl();
                    $image->setData('small_image_url', $thumb);
                }
            }
        }
        return $images[$id];
    }

    /**
     * Retrieve original gallery block
     *
     * @return mixed
     */
    public function getOriginalBlock()
    {
        $data = $this->_coreRegistry->registry('magictoolbox');
        return is_null($data) ? null : $data['blocks']['product.info.media.image'];
    }

    /**
     * Retrieve another gallery block
     *
     * @return mixed
     */
    public function getAnotherBlock()
    {
        $data = $this->_coreRegistry->registry('magictoolbox');
        if ($data) {
            $skip = true;
            foreach ($data['blocks'] as $name => $block) {
                if ($name == 'product.info.media.magicslideshow') {
                    $skip = false;
                    continue;
                }
                if ($skip) {
                    continue;
                }
                if ($block) {
                    return $block;
                }
            }
        }
        return null;
    }

    /**
     * Check for installed modules, which can operate in cooperative mode
     *
     * @return bool
     */
    public function isCooperativeModeAllowed()
    {
        $data = $this->_coreRegistry->registry('magictoolbox');
        return is_null($data) ? false : $data['cooperative-mode'];
    }

    /**
     * Before rendering html, but after trying to load cache
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->renderGalleryHtml();
        return parent::_beforeToHtml();
    }

    /**
     * Get rendered HTML
     *
     * @param integer $id
     * @return string
     */
    public function getRenderedHtml($id = null)
    {
        if (is_null($id)) {
            $id = $this->getProduct()->getId();
        }
        return isset($this->renderedGalleryHtml[$id]) ? $this->renderedGalleryHtml[$id] : '';
    }

    /**
     * Render gallery block HTML
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $isAssociatedProduct
     * @param array $data
     * @return $this
     */
    public function renderGalleryHtml($product = null, $isAssociatedProduct = false, $data = [])
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }
        $this->currentProductId = $id = $product->getId();
        if (!isset($this->renderedGalleryHtml[$id])) {
            $this->toolObj->params->setProfile('product');
            $name = $product->getName();
            $magicslideshowData = [];

            $images = $this->getGalleryImagesCollection($product);

            $originalBlock = $this->getOriginalBlock();

            if (!$images->count()) {
                $this->renderedGalleryHtml[$id] = $isAssociatedProduct ? '' : $this->getPlaceholderHtml();
                return $this;
            }

            foreach ($images as $image) {

                $mediaType = $image->getMediaType();
                $isImage = $mediaType == 'image';
                $isVideo = $mediaType == 'external-video';

                if (!$isImage && !$isVideo) {
                    continue;
                }

                $label = $isImage ? $image->getLabel() : $image->getVideoTitle();
                if (empty($label)) {
                    $label = $name;
                }

                $content = '';
                if ($isVideo) {
                    $content = $this->getVideoHtml($image->getVideoUrl(), $id);
                }
                $magicslideshowData[] = [
                    'title' => $label,
                    'img' => $image->getData('medium_image_url'),
                    'thumb' => $image->getData('small_image_url'),
                    'fullscreen' => $image->getData('large_image_url'),
                    'content' => $content,
                ];
            }
            if (empty($magicslideshowData)) {
                if ($originalBlock) {
                    $this->renderedGalleryHtml[$id] = $isAssociatedProduct ? '' : $this->getPlaceholderHtml();
                }
                return $this;
            }

            $this->renderedGalleryHtml[$id] = $this->toolObj->getMainTemplate($magicslideshowData, ['id' => "MagicSlideshow-product-{$id}"]);

            $this->renderedGalleryHtml[$id] = '<div class="MagicToolboxContainer">'.$this->renderedGalleryHtml[$id].'</div>';

        }
        return $this;
    }

    /**
     * Get video HTML
     *
     * @param string $url
     * @param integer $id
     * @return string
     */
    public function getVideoHtml($url, $id)
    {
        static $videoIndex = 0;
        $url = parse_url($url);
        if (!$url) {
            return '';
        }

        $isVimeo = false;
        $videoCode = null;
        if (preg_match('#youtube\.com|youtu\.be#', $url['host'])) {
            if (isset($url['query']) && preg_match('#\bv=([^&]+)(?:&|$)#', $url['query'], $matches)) {
                $videoCode = $matches[1];
            } elseif (isset($url['path']) && preg_match('#^/(?:embed/|v/)?([^/\?]+)(?:/|\?|$)#', $url['path'], $matches)) {
                $videoCode = $matches[1];
            }
        } elseif (preg_match('#(?:www\.|player\.)?vimeo\.com#', $url['host'])) {
            $isVimeo = true;
            if (isset($url['path']) && preg_match('#/(?:channels/[^/]+/|groups/[^/]+/videos/|album/[^/]+/video/|video/|)(\d+)(?:/|\?|$)#', $url['path'], $matches)) {
                $videoCode = $matches[1];
            }
        }

        if (!$videoCode) {
            return '';
        }

        $videoIndex++;
        $scheme = empty($url['scheme']) ? 'https' : $url['scheme'];

        $videoSettings = $this->magicToolboxHelper->getVideoSettings();
        $rel = '&rel='.$videoSettings['showRelated'];
        $loop = '&loop='.$videoSettings['videoAutoRestart'];

        $src = $scheme.'://'.($isVimeo ? 'player.vimeo.com/video' : 'www.youtube.com/embed').'/'.$videoCode.'?autoplay=0'.$rel.$loop;
        $id = ($isVimeo ? 'vimeo' : 'youtube').'Video'.$id.'-'.$videoIndex;
        if (!$isVimeo) {
            $src .= '&enablejsapi=1';
        }

        return '<iframe id="'.$id.'" type="text/html" src="'.$src.'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen ></iframe>';
    }

    /**
     * Get placeholder HTML
     *
     * @return string
     */
    public function getPlaceholderHtml()
    {
        static $html = null;
        if ($html === null) {
            $placeholderUrl = $this->_imageHelper->getDefaultPlaceholderUrl('image');
            list($width, $height) = $this->magicToolboxHelper->magicToolboxGetSizes('thumb');
            $html = '<div class="MagicToolboxContainer placeholder" style="width: '.$width.'px;height: '.$height.'px">'.
                    '<span class="align-helper"></span>'.
                    '<img src="'.$placeholderUrl.'"/>'.
                    '</div>';
        }
        return $html;
    }
}
