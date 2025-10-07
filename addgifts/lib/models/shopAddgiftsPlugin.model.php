<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 *
 * @deprecated Удалить в следующих обновлениях
 */
class shopAddgiftsPluginModel extends waModel
{
    protected $table = "shop_addgifts";


    /**
     * Возвращает массив ID непосредственных подароков у товара
     *
     * @version 1.5
     * @param $id_product int
     * @return array  при отсутсвие пустой массив
     */
    public static function getDirectlyGiftIds($id_product)
    {
        $model = new self();
        $gifts = $model->select('gift_id')->where('product_id = i:0', $id_product)->order('id ASC')->query()->fetchAll('gift_id');
        return array_keys($gifts);
    }

    /**
     * Возвращает массив ID непосредственных подароков у категории
     *
     * @version 1.5
     * @param $id_category int
     * @return array при отсутсвие пустой массив
     */
    public static function getDirectlyCategoryGiftIds($id_category)
    {
        $model = new self();
        $gifts = $model->select('gift_id')->where('category_id = i:0', $id_category)->order('id ASC')->query()->fetchAll('gift_id');
        return array_keys($gifts);
    }

    /**
     * Преобразуем массив ID в массив товаров
     * @version 1.5
     * @param $ids array
     * @return array
     */
    public static function getGiftsByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }

        $result = array();
        $model = new shopProductModel();

        foreach ($ids as $gift_id) {
            $gift = $model->getById($gift_id);
            if ($gift) {
                $result[$gift_id] = $gift;
            }
        }
        return $result;

    }

    /**
     * Возвращает массив подарков непосредственно у товара
     * @param $id_product int id товара
     * @return array
     */
    public static function getDirectlyGifts($id_product)
    {
        $ids = self::getDirectlyGiftIds($id_product);
        return self::getGiftsByIds($ids);
    }

    /**
     * Возвращает массив подарков непосредственно у категории
     * @param $id_category int id товара
     * @return array
     */
    public static function getDirectlyCategoryGifts($id_category)
    {
        $ids = self::getDirectlyCategoryGiftIds($id_category);
        return self::getGiftsByIds($ids);
    }

    /**
     * Возвращает подарки у родительских категорий
     * @param $id_category
     * @return array
     */
    public static function getParentCategoriesGiftIds($id_category)
    {

        $category_model = new shopCategoryModel();
        $path = $category_model->getPath($id_category);

        if (empty($path)) {
            return array();
        }

        //Если категория одна быстрее посмотреть непосредственно ее
        if (count($path) == 1) {

            $category = array_shift($path);
            return self::getDirectlyCategoryGiftIds($category['id']);

        } else {

            //Ищем сразу во всех дочерних одним запросом с сортировкой по порядку
            $model = new self();

            $ids = implode(",", array_keys($path));
            $sql = "SELECT category_id, gift_id FROM `shop_addgifts` WHERE category_id in ($ids) order by FIELD(category_id, $ids), id ASC";

            $gifts = $model->query($sql)->fetchAll('gift_id');

            $result = array();
            $first_category = null;

            foreach ($gifts as $gift) {

                if (is_null($first_category)) {
                    $first_category = $gift['category_id'];
                }

                if ($gift['category_id'] != $first_category) {
                    break;
                }

                $result[] = $gift['gift_id'];
            }

            return $result;
        }

    }

}