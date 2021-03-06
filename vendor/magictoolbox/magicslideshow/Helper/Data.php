<?php

namespace MagicToolbox\MagicSlideshow\Helper;

/**
 * Data helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Model factory
     * @var \MagicToolbox\MagicSlideshow\Model\ConfigFactory
     */
    protected $modelConfigFactory = null;

    /**
     * MagicSlideshow module core class
     *
     * @var \MagicToolbox\MagicSlideshow\Classes\MagicSlideshowModuleCoreClass
     *
     */
    protected $magicslideshow = null;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $staticDirectory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Base url
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Base url for media
     *
     * @var string
     */
    protected $baseMediaUrl;

    /**
     * Base url for static
     *
     * @var string
     */
    protected $baseStaticUrl;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * Product list block
     *
     * @var \Magento\Catalog\Block\Product\ListProduct
     */
    protected $listProductBlock = null;

    /**
     * Frontend flag
     *
     * @var bool
     */
    protected $isFrontend = true;

    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \MagicToolbox\MagicSlideshow\Model\ConfigFactory $modelConfigFactory
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \MagicToolbox\MagicSlideshow\Classes\MagicSlideshowModuleCoreClass $magicslideshow
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \MagicToolbox\MagicSlideshow\Model\ConfigFactory $modelConfigFactory,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MagicToolbox\MagicSlideshow\Classes\MagicSlideshowModuleCoreClass $magicslideshow,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->modelConfigFactory = $modelConfigFactory;
        $this->magicslideshow = $magicslideshow;
        $this->isFrontend = ($appState->getAreaCode() != \Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->initToolObj();
        $this->imageHelper = $imageHelperFactory->create();
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->staticDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::STATIC_VIEW);
        $this->storeManager = $storeManager;
        $this->baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $this->baseMediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $this->baseStaticUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
        $this->coreRegistry = $registry;
        $this->objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * Init main tool object
     *
     * @return void
     */
    protected function initToolObj()
    {
        $model = $this->modelConfigFactory->create();
        $collection = $model->getCollection();
        $collection->addFieldToFilter('platform', 0);
        $collection->addFieldToFilter('status', ['neq' => 0]);
        $data = $collection->getData();
        foreach ($data as $key => $param) {
            $this->magicslideshow->params->setValue($param['name'], $param['value'], $param['profile']);
        }
        //NOTE: apply tranlations
        if ($this->isFrontend) {
            $transParams = [
                'message',
            ];
            foreach ($this->magicslideshow->params->getProfiles() as $profile) {
                foreach ($transParams as $name) {
                    $value = $this->magicslideshow->params->getValue($name, $profile);
                    $this->magicslideshow->params->setValue($name, '' . __($value), $profile);
                }
            }
        }
    }

    /**
     * Get main tool object
     *
     * @return \MagicToolbox\MagicSlideshow\Classes\MagicSlideshowModuleCoreClass
     */
    public function getToolObj()
    {
        return $this->magicslideshow;
    }

    /**
     * Retrieve video settings data
     *
     * @return array
     */
    public function getVideoSettings()
    {
        static $videoSettingData = null;
        if ($videoSettingData === null) {
            $videoSettingData = [
                'playIfBase' => (int)$this->scopeConfig->getValue(\Magento\ProductVideo\Helper\Media::XML_PATH_PLAY_IF_BASE),
                'showRelated' => (int)$this->scopeConfig->getValue(\Magento\ProductVideo\Helper\Media::XML_PATH_SHOW_RELATED),
                'videoAutoRestart' => (int)$this->scopeConfig->getValue(\Magento\ProductVideo\Helper\Media::XML_PATH_VIDEO_AUTO_RESTART),
            ];
        }
        return $videoSettingData;
    }

    /**
     * Public method to get image sizes
     *
     * @return array
     */
    public function magicToolboxGetSizes($sizeType, $originalSizes = [])
    {
        $w = $this->magicslideshow->params->getValue($sizeType.'-max-width');
        $h = $this->magicslideshow->params->getValue($sizeType.'-max-height');
        if (empty($w)) {
            $w = 0;
        }
        if (empty($h)) {
            $h = 0;
        }
        if ($this->magicslideshow->params->checkValue('square-images', 'No')) {
            //NOTE: fix for bad images
            if (empty($originalSizes[0]) || empty($originalSizes[1])) {
                return [$w, $h];
            }
            list($w, $h) = $this->calculateSize($originalSizes[0], $originalSizes[1], $w, $h);
        } else {
            $h = $w = $h ? ($w ? min($w, $h) : $h) : $w;
        }
        return [$w, $h];
    }

    /**
     * Public method to calculate sizes
     *
     * @return array
     */
    private function calculateSize($originalW, $originalH, $maxW = 0, $maxH = 0)
    {
        if (!$maxW && !$maxH) {
            return [$originalW, $originalH];
        } elseif (!$maxW) {
            $maxW = ($maxH * $originalW) / $originalH;
        } elseif (!$maxH) {
            $maxH = ($maxW * $originalH) / $originalW;
        }

        //NOTE: to do not stretch small images
        if (($originalW < $maxW) && ($originalH < $maxH)) {
            return [$originalW, $originalH];
        }

        $sizeDepends = $originalW/$originalH;
        $placeHolderDepends = $maxW/$maxH;
        if ($sizeDepends > $placeHolderDepends) {
            $newW = $maxW;
            $newH = $originalH * ($maxW / $originalW);
        } else {
            $newW = $originalW * ($maxH / $originalH);
            $newH = $maxH;
        }
        return [round($newW), round($newH)];
    }

    /**
     * Get media directory
     *
     * @return \Magento\Framework\Filesystem\Directory\Write
     */
    public function getMediaDirectory()
    {
        return $this->mediaDirectory;
    }

    /**
     * Get static directory
     *
     * @return \Magento\Framework\Filesystem\Directory\Write
     */
    public function getStaticDirectory()
    {
        return $this->staticDirectory;
    }

    /**
     * Get base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Get base url for media
     *
     * @return string
     */
    public function getBaseMediaUrl()
    {
        return $this->baseMediaUrl;
    }

    /**
     * Get base url for static
     *
     * @return string
     */
    public function getBaseStaticUrl()
    {
        return $this->baseStaticUrl;
    }

    /**
     * Get HTML data (product list page)
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $isAssociatedProduct
     * @param array $mediaAttributeCodes
     * @return string
     */
    public function getHtmlData($product, $isAssociatedProduct = false, $mediaAttributeCodes = ['small_image'])
    {
        static $_html = [];
        $id = $product->getId();
        $key = implode('_', $mediaAttributeCodes);
        if (!isset($_html[$key])) {
            $_html[$key] = [];
        }
        $html = & $_html[$key];
        if (!isset($html[$id])) {
            $this->magicslideshow->params->setProfile('category');

            /** @var $listProductBlock \Magento\Catalog\Block\Product\ListProduct */
            $listProductBlock = $this->getListProductBlock();

            if (!$listProductBlock) {
                $listProductBlock = $this->objectManager->get(
                    \Magento\Catalog\Block\Product\ListProduct::class
                );
            }

            $styleAttr = '';
            if ($listProductBlock) {
                $isGridMode = ($listProductBlock->getMode() == 'grid');
                $mediaId = $isGridMode ? 'category_page_grid' : 'category_page_list';
                $productImage = $listProductBlock->getImage($product, $mediaId);
                $productImageWidth = $productImage->getWidth();
                $styleAttr = ($isGridMode ? '' : 'style="width: '.$productImageWidth.'px;"');
            } else {
                //$isGridMode = true;
                $mediaId = 'category_page_grid';
                //list($productImageWidth, ) = $this->magicToolboxGetSizes('thumb');
            }

            $images = $this->getGalleryData($product, $isAssociatedProduct, $mediaId);
            if (!count($images)) {
                $html[$id] = $isAssociatedProduct ? '' : $this->getPlaceholderHtml($product, $mediaId);
                return $html[$id];
            }

            $html[$id] = $this->magicslideshow->getMainTemplate($images, ['id' => "MagicSlideshow-category-{$id}"]);
            $html[$id] = '<div class="MagicToolboxContainer" '.$styleAttr.'>'.$html[$id].'</div>';
        }

        return $html[$id];
    }

    /**
     * Retrieve another renderer
     *
     * @return mixed
     */
    public function getAnotherRenderer()
    {
        $data = $this->coreRegistry->registry('magictoolbox_category');
        if ($data) {
            $skip = true;
            foreach ($data['renderers'] as $name => $renderer) {
                if ($name == 'configurable.magicslideshow') {
                    $skip = false;
                    continue;
                }
                if ($skip) {
                    continue;
                }
                if ($renderer) {
                    return $renderer;
                }
            }
        }
        return null;
    }

    /**
     * Get placeholder HTML
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $mediaId
     * @return string
     */
    public function getPlaceholderHtml($product, $mediaId)
    {
        static $html = null;
        if ($html === null) {
            $placeholderUrl = $this->imageHelper->init($product, $mediaId)->getUrl();
            list($width, $height) = $this->magicToolboxGetSizes('thumb');
            $html = '<div class="MagicToolboxContainer placeholder" style="width: '.$width.'px;height: '.$height.'px">'.
                    '<span class="align-helper"></span>'.
                    '<img src="'.$placeholderUrl.'"/>'.
                    '</div>';
        }
        return $html;
    }

    /**
     * Set product list block
     *
     * @param \Magento\Catalog\Block\Product\ListProduct $block
     */
    public function setListProductBlock(\Magento\Catalog\Block\Product\ListProduct $block)
    {
        $this->listProductBlock = $block;
    }

    /**
     * Get product list block
     *
     * @return \Magento\Catalog\Block\Product\ListProduct
     */
    public function getListProductBlock()
    {
        return $this->listProductBlock;
    }

    /**
     * Retrieve gallery data
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $isAssociatedProduct
     * @param string $mediaId
     * @return array
     */
    private function getGalleryData($product, $isAssociatedProduct, $mediaId)
    {
        static $images = [];
        $id = $product->getId();
        if (!isset($images[$id])) {
            $images[$id] = [];
            $link = $this->magicslideshow->params->checkValue('link-to-product-page', 'Yes');
            //TODO: get parent product link for associated products
            $link = $link && !$isAssociatedProduct ? $product->getProductUrl() : false;
            $smallImage = $product->getSmallImage();
            if ($smallImage && 'no_selection' != $smallImage) {
                $images[$id][] = [
                    'title' => $product->getSmallImageLabel(),
                    'file' => $smallImage,
                    'link' => $link,
                ];
            }

            $mediaGalleryImages = $product->getMediaGalleryImages();

            $reload = !($mediaGalleryImages instanceof \Magento\Framework\Data\Collection && $mediaGalleryImages->count());

            if ($reload) {
                $product->load($id);
                $mediaGalleryImages = $product->getMediaGalleryImages();
            }

            if ($mediaGalleryImages instanceof \Magento\Framework\Data\Collection) {
                foreach ($mediaGalleryImages as $image) {
                    /* @var \Magento\Framework\DataObject $image */
                    $mediaType = $image->getMediaType();
                    if ($mediaType != 'image') {
                        continue;
                    }
                    if ($smallImage == $image->getFile()) {
                        continue;
                    }
                    $images[$id][] = [
                        'title' => $image->getLabel(),
                        'file' => $image->getFile(),
                        'link' => $link,
                    ];
                }
            }

            foreach ($images[$id] as $key => $image) {

                $img = $this->imageHelper->init($product, $mediaId, ['width' => null, 'height' => null])
                        ->setImageFile($image['file'])
                        ->getUrl();

                $iPath = $this->mediaDirectory->getAbsolutePath($product->getMediaConfig()->getMediaPath($image['file']));
                if (!is_file($iPath)) {
                    if (strpos($img, $this->baseMediaUrl) === 0) {
                        $iPath = str_replace($this->baseMediaUrl, '', $img);
                        $iPath = $this->mediaDirectory->getAbsolutePath($iPath);
                    } else {
                        $iPath = str_replace($this->baseStaticUrl, '', $img);
                        $iPath = $this->staticDirectory->getAbsolutePath($iPath);
                    }
                }
                try {
                    $originalSizeArray = getimagesize($iPath);
                } catch (\Exception $exception) {
                    $originalSizeArray = [0, 0];
                }

                if ($this->magicslideshow->params->checkValue('square-images', 'Yes')) {
                    $bigImageSize = ($originalSizeArray[0] > $originalSizeArray[1]) ? $originalSizeArray[0] : $originalSizeArray[1];
                    $img = $this->imageHelper->init($product, $mediaId)
                            ->setImageFile($image['file'])
                            ->resize($bigImageSize)
                            ->getUrl();
                }

                list($w, $h) = $this->magicToolboxGetSizes('thumb', $originalSizeArray);
                $medium = $this->imageHelper->init($product, $mediaId, ['width' => $w, 'height' => $h])
                        ->setImageFile($image['file'])
                        ->getUrl();

                list($w, $h) = $this->magicToolboxGetSizes('selector', $originalSizeArray);
                $thumb = $this->imageHelper->init($product, $mediaId, ['width' => $w, 'height' => $h])
                        ->setImageFile($image['file'])
                        ->getUrl();
                $images[$id][$key]['img'] = $medium;
                $images[$id][$key]['thumb'] = $thumb;
                $images[$id][$key]['fullscreen'] = $img;
            }
        }
        return $images[$id];
    }

    /**
     * Retrieve image helper
     *
     * @return \Magento\Catalog\Helper\Image
     */
    public function getImageHelper()
    {
        return $this->imageHelper;
    }

    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        static $version = null;
        if ($version === null) {
            $productMetadata = $this->objectManager->get(
                \Magento\Framework\App\ProductMetadataInterface::class
            );
            $version = $productMetadata->getVersion();
        }
        return $version;
    }

    /**
     * Get component registrar
     *
     * @return \Magento\Framework\Component\ComponentRegistrar
     */
    protected function getComponentRegistrar()
    {
        static $componentRegistrar = null;
        if ($componentRegistrar === null) {
            $componentRegistrar = $this->objectManager->get(
                \Magento\Framework\Component\ComponentRegistrar::class
            );
        }

        return $componentRegistrar;
    }

    /**
     * Get module version
     *
     * @param string $name
     * @return string | bool
     */
    public function getModuleVersion($name)
    {
        static $versions = [];

        if (isset($versions[$name])) {
            return $versions[$name];
        }

        $versions[$name] = false;

        $moduleDir = $this->getComponentRegistrar()->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            $name
        );

        $moduleInfo = json_decode(file_get_contents($moduleDir . '/composer.json'));

        if (is_object($moduleInfo) && isset($moduleInfo->version)) {
            $versions[$name] = $moduleInfo->version;
        }

        return $versions[$name];
    }

    /**
     * Get app cache
     *
     * @return \Magento\Framework\App\CacheInterface
     */
    public function getAppCache()
    {
        /** @var \Magento\Framework\App\CacheInterface $cache */
        static $cache = null;

        if ($cache === null) {
            $cache = $this->objectManager->get(\Magento\Framework\App\CacheInterface::class);
        }

        return $cache;
    }

    /**
     * Get enabled module's data (name and version)
     *
     * @return array
     */
    public function getModulesData()
    {
        static $data = null;

        if ($data !== null) {
            return $data;
        }

        $cache = $this->getAppCache();
        $cacheId = 'magictoolbox_modules_data';

        $data = $cache->load($cacheId);
        if (false !== $data) {
            $data = $this->getUnserializer()->unserialize($data);
            return $data;
        }

        $data = [];

        $mtModules = [
            'MagicToolbox_Sirv',
            'MagicToolbox_Magic360',
            'MagicToolbox_MagicZoomPlus',
            'MagicToolbox_MagicZoom',
            'MagicToolbox_MagicThumb',
            'MagicToolbox_MagicScroll',
            'MagicToolbox_MagicSlideshow',
        ];

        $enabledModules = $this->objectManager->create(\Magento\Framework\Module\ModuleList::class)->getNames();

        foreach ($mtModules as $name) {
            if (in_array($name, $enabledModules)) {
                $data[$name] = $this->getModuleVersion($name);
            }
        }

        $serializer = $this->getSerializer();
        //NOTE: cache lifetime (in seconds)
        $cache->save($serializer->serialize($data), $cacheId, [], 600);

        return $data;
    }

    /**
     * Public method for retrieve statuses
     *
     * @param string $profile
     * @param bool $force
     * @return array
     */
    public function getStatuses($profile = false, $force = false)
    {
        static $result = null;
        if (is_null($result) || $force) {
            $result = [];
            $model = $this->modelConfigFactory->create();
            $collection = $model->getCollection();
            $collection->addFieldToFilter('platform', 0);
            $data = $collection->getData();
            foreach ($data as $key => $param) {
                if (!isset($result[$param['profile']])) {
                    $result[$param['profile']] = [];
                }
                $result[$param['profile']][$param['name']] = $param['status'];
            }
        }
        return isset($result[$profile]) ? $result[$profile] : $result;
    }

    /**
     * Get unserializer
     *
     * @return \Magento\Framework\Unserialize\Unserialize
     */
    public function getUnserializer()
    {
        static $unserializer = null;

        if ($unserializer === null) {
            $unserializer = $this->objectManager->get(
                \Magento\Framework\Unserialize\Unserialize::class
            );
        }

        return $unserializer;
    }

    /**
     * Get serializer
     *
     * @return \Magento\Framework\Serialize\Serializer\Serialize | \Zend\Serializer\Adapter\PhpSerialize
     */
    public function getSerializer()
    {
        static $serializer = null;

        if ($serializer === null) {
            if (class_exists('\Magento\Framework\Serialize\Serializer\Serialize', false)) {
                //NOTE: Magento v2.2.x and v2.3.x
                $serializer = $this->objectManager->get(
                    \Magento\Framework\Serialize\Serializer\Serialize::class
                );
            } else {
                //NOTE: Magento v2.1.x
                $serializer = $this->objectManager->get(
                    \Zend\Serializer\Adapter\PhpSerialize::class
                );
            }
        }

        return $serializer;
    }

    /**
     * Public method for retrieve config map
     *
     * @return array
     */
    public function getConfigMap()
    {
        return $this->getUnserializer()->unserialize('a:3:{s:7:"default";a:8:{s:7:"General";a:1:{i:0;s:28:"include-headers-on-all-pages";}s:24:"Positioning and Geometry";a:5:{i:0;s:15:"thumb-max-width";i:1;s:16:"thumb-max-height";i:2;s:18:"selector-max-width";i:3;s:19:"selector-max-height";i:4;s:13:"square-images";}s:15:"Common settings";a:8:{i:0;s:5:"width";i:1;s:6:"height";i:2;s:11:"orientation";i:3;s:6:"arrows";i:4;s:4:"loop";i:5;s:6:"effect";i:6;s:12:"effect-speed";i:7;s:13:"effect-easing";}s:8:"Autoplay";a:5:{i:0;s:8:"autoplay";i:1;s:14:"slide-duration";i:2;s:7:"shuffle";i:3;s:8:"kenburns";i:4;s:5:"pause";}s:9:"Selectors";a:5:{i:0;s:15:"selectors-style";i:1;s:9:"selectors";i:2;s:13:"selectors-eye";i:3;s:15:"bullets-preview";i:4;s:14:"selectors-fill";}s:7:"Caption";a:1:{i:0;s:7:"caption";}s:14:"Other settings";a:5:{i:0;s:10:"fullscreen";i:1;s:7:"preload";i:2;s:8:"keyboard";i:3;s:11:"show-loader";i:4;s:9:"autostart";}s:13:"Miscellaneous";a:3:{i:0;s:20:"link-to-product-page";i:1;s:12:"show-message";i:2;s:7:"message";}}s:7:"product";a:8:{s:7:"General";a:1:{i:0;s:13:"enable-effect";}s:24:"Positioning and Geometry";a:5:{i:0;s:15:"thumb-max-width";i:1;s:16:"thumb-max-height";i:2;s:18:"selector-max-width";i:3;s:19:"selector-max-height";i:4;s:13:"square-images";}s:15:"Common settings";a:8:{i:0;s:5:"width";i:1;s:6:"height";i:2;s:11:"orientation";i:3;s:6:"arrows";i:4;s:4:"loop";i:5;s:6:"effect";i:6;s:12:"effect-speed";i:7;s:13:"effect-easing";}s:8:"Autoplay";a:5:{i:0;s:8:"autoplay";i:1;s:14:"slide-duration";i:2;s:7:"shuffle";i:3;s:8:"kenburns";i:4;s:5:"pause";}s:9:"Selectors";a:5:{i:0;s:15:"selectors-style";i:1;s:9:"selectors";i:2;s:13:"selectors-eye";i:3;s:15:"bullets-preview";i:4;s:14:"selectors-fill";}s:7:"Caption";a:1:{i:0;s:7:"caption";}s:14:"Other settings";a:5:{i:0;s:10:"fullscreen";i:1;s:7:"preload";i:2;s:8:"keyboard";i:3;s:11:"show-loader";i:4;s:9:"autostart";}s:13:"Miscellaneous";a:2:{i:0;s:12:"show-message";i:1;s:7:"message";}}s:8:"category";a:8:{s:7:"General";a:1:{i:0;s:13:"enable-effect";}s:24:"Positioning and Geometry";a:5:{i:0;s:15:"thumb-max-width";i:1;s:16:"thumb-max-height";i:2;s:18:"selector-max-width";i:3;s:19:"selector-max-height";i:4;s:13:"square-images";}s:15:"Common settings";a:8:{i:0;s:5:"width";i:1;s:6:"height";i:2;s:11:"orientation";i:3;s:6:"arrows";i:4;s:4:"loop";i:5;s:6:"effect";i:6;s:12:"effect-speed";i:7;s:13:"effect-easing";}s:8:"Autoplay";a:5:{i:0;s:8:"autoplay";i:1;s:14:"slide-duration";i:2;s:7:"shuffle";i:3;s:8:"kenburns";i:4;s:5:"pause";}s:9:"Selectors";a:5:{i:0;s:15:"selectors-style";i:1;s:9:"selectors";i:2;s:13:"selectors-eye";i:3;s:15:"bullets-preview";i:4;s:14:"selectors-fill";}s:7:"Caption";a:1:{i:0;s:7:"caption";}s:14:"Other settings";a:5:{i:0;s:10:"fullscreen";i:1;s:7:"preload";i:2;s:8:"keyboard";i:3;s:11:"show-loader";i:4;s:9:"autostart";}s:13:"Miscellaneous";a:3:{i:0;s:20:"link-to-product-page";i:1;s:12:"show-message";i:2;s:7:"message";}}}');
    }
}
