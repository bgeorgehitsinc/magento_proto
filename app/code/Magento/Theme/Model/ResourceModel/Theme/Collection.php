<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Model\ResourceModel\Theme;

use Magento\Framework\App\Area;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Design\Theme\Label\ListInterface as ThemeLabelListInterface;
use Magento\Framework\View\Design\Theme\ListInterface as ThemeListInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\ResourceModel\Theme as ResourceTheme;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme as ModelTheme;

/**
 * Theme collection
 */
class Collection extends AbstractCollection implements ThemeLabelListInterface, ThemeListInterface
{
    /**
     * Default page size
     */
    const DEFAULT_PAGE_SIZE = 6;

    /**
     * @var string
     */
    protected $_idFieldName = 'theme_id';

    /**
     * Collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ModelTheme::class, ResourceTheme::class);
    }

    /**
     * Add title for parent themes
     *
     * @return $this
     */
    public function addParentTitle()
    {
        $this->getSelect()->joinLeft(
            ['parent' => $this->getMainTable()],
            'main_table.parent_id = parent.theme_id',
            ['parent_theme_title' => 'parent.theme_title']
        );
        return $this;
    }

    /**
     * Add area filter
     *
     * @param string $area
     * @return $this
     */
    public function addAreaFilter($area = Area::AREA_FRONTEND)
    {
        $this->getSelect()->where('main_table.area=?', $area);
        return $this;
    }

    /**
     * Add type filter in relations
     *
     * @param int $typeParent
     * @param int $typeChild
     * @return $this
     */
    public function addTypeRelationFilter($typeParent, $typeChild)
    {
        $this->getSelect()->join(
            ['parent' => $this->getMainTable()],
            'main_table.parent_id = parent.theme_id',
            ['parent_type' => 'parent.type']
        )->where(
            'parent.type = ?',
            $typeParent
        )->where(
            'main_table.type = ?',
            $typeChild
        );
        return $this;
    }

    /**
     * Add type filter
     *
     * @param string|array $type
     * @return $this
     */
    public function addTypeFilter($type)
    {
        $this->addFieldToFilter('main_table.type', ['in' => $type]);
        return $this;
    }

    /**
     * Filter visible themes in backend (physical and virtual only)
     *
     * @return $this
     */
    public function filterVisibleThemes()
    {
        $this->addTypeFilter(
            [
                ThemeInterface::TYPE_PHYSICAL,
                ThemeInterface::TYPE_VIRTUAL,
            ]
        );
        return $this;
    }

    /**
     * Return array for select field
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('theme_id', 'theme_title');
    }

    /**
     * Return array for grid column
     *
     * @return array
     */
    public function toOptionHash()
    {
        return $this->_toOptionHash('theme_id', 'theme_title');
    }

    /**
     * Get theme from DB by area and theme_path
     *
     * @param string $fullPath
     * @return ModelTheme
     */
    public function getThemeByFullPath($fullPath)
    {
        $this->_reset()->clear();
        list($area, $themePath) = explode('/', $fullPath, 2);
        $this->addFieldToFilter('area', $area);
        $this->addFieldToFilter('theme_path', $themePath);

        return $this->getFirstItem();
    }

    /**
     * Set page size
     *
     * @param int $size
     * @return $this
     */
    public function setPageSize($size = self::DEFAULT_PAGE_SIZE)
    {
        return parent::setPageSize($size);
    }

    /**
     * Update all child themes relations
     *
     * @param ThemeInterface $themeModel
     * @return $this
     */
    public function updateChildRelations(ThemeInterface $themeModel)
    {
        $parentThemeId = $themeModel->getParentId();
        $this->addFieldToFilter('parent_id', ['eq' => $themeModel->getId()])->load();

        /** @var ThemeInterface $theme */
        foreach ($this->getItems() as $theme) {
            $theme->setParentId($parentThemeId)->save();
        }
        return $this;
    }

    /**
     * Filter frontend physical theme.
     * All themes or per page if set page and page size (page size is optional)
     *
     * @param int $page
     * @param int $pageSize
     * @return $this
     */
    public function filterPhysicalThemes(
        $page = null,
        $pageSize = ThemeCollection::DEFAULT_PAGE_SIZE
    ) {
        $this->addAreaFilter(
            Area::AREA_FRONTEND
        )->addTypeFilter(
            ThemeInterface::TYPE_PHYSICAL
        );
        if ($page) {
            $this->setPageSize($pageSize)->setCurPage($page);
        }
        return $this;
    }

    /**
     * Filter theme customization
     *
     * @param string $area
     * @param int $type
     * @return $this
     */
    public function filterThemeCustomizations(
        $area = Area::AREA_FRONTEND,
        $type = ThemeInterface::TYPE_VIRTUAL
    ) {
        $this->addAreaFilter($area)->addTypeFilter($type);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLabels()
    {
        $labels = $this->loadRegisteredThemes();
        return $labels->toOptionArray();
    }

    /**
     * @return $this
     */
    public function loadRegisteredThemes()
    {
        $this->_reset()->clear();
        return $this->setOrder('theme_title', DataCollection::SORT_ORDER_ASC)
            ->filterVisibleThemes()->addAreaFilter(Area::AREA_FRONTEND);
    }
}
