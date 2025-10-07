<?php

/**
 * расширение: реализация коллекций товаров
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitCollection
{

    /** @var shopProductsCollection */
    public $collection = null;

    //Подключение таблицы категорий
    public $collectionCategoryAlias = null;

    //Параметры выбора коллекции
    public $collectionOptions = array('ids' => array());

    /**
     * Хук => Формирование колекции подарков
     * @param $params
     * @return bool
     */
    public function productsCollection($params)
    {

        /** @var shopProductsCollection $collection */
        $collection = $params['collection'];
        $hash = $collection->getHash();

        if ($hash[0] != 'giftrule') {
            return false;
        }

        $this->collectionOptions = $this->getCollectionOptions($hash);

        //wa_dump($this->collectionOptions);

        //Все товары с подарками
        if (empty($this->collectionOptions['ids'])) {
            $rules = shopAddgiftsRulesModel::getRules();
        } else {

            $last_title = '';

            //Товары по конкретному правилу
            foreach ($this->collectionOptions['ids'] as $id_rule) {
                $id_rule = (int)$id_rule;
                $rule = shopAddgiftsRulesModel::getRule($id_rule);

                if (empty($rule) || (!empty($rule['is_cart_sum']))) {
                    continue;
                }

                $last_title = !empty($rule['name']) ? $rule['name'] : '';
                $rules[] = $rule;
            }

            if ((count($rules) == 1) && (!empty($last_title))) {
                $collection->addTitle($last_title);
            }
        }

        //Нет активных правил
        if (empty($rules)) {
            $collection->addWhere('false');
            return true;
        }

        //Сохраняем коллекцию и обнуляем переменные
        $this->collection = $collection;
        $this->collectionCategoryAlias = null;

        $where_or = array();

        foreach ($rules as $rule) {

            if (!empty($rule['is_stop'])) {
                continue;
            }

            //Учитываем настройку игнорировать подарки
            if (empty($this->collectionOptions['ignore_gifts'])) {

                //Закончились подарки
                if (empty($this->checkGiftsAvailable($rule))) {
                    continue;
                }
            }

            //Получаем условия выборки
            $where_or[] = $this->getCollectionWhere($rule['group']);
        }

        //Нет активных условий
        if (empty($where_or)) {
            $collection->addWhere('false');
            return true;
        }

        //Группируем условия от нескольких групп
        $where = '(' . implode(' OR ', $where_or) . ')';
        $this->collection->addWhere($where);

        return true;
    }

    public function getCollectionOptions($hash)
    {
        $result = array('ids' => array(), 'ignore_gifts' => false);
        if (empty($hash[1])) {
            return $result;
        }

        if (strpos($hash[1], '?') !== false) {
            $list = explode('?', $hash[1]);
        } else {
            $list = explode('/', $hash[1]);
        }


        if (!empty($list[0])) {
            $result['ids'] = explode(",", $list[0]);
        }

        if (!empty($list[1])) {
            $options = explode('&', $list[1]);
            foreach ($options as $opt) {
                $opt = explode('=', $opt);

                $result[$opt[0]] = isset($opt[1]) ? $opt[1] : 1;
            }
        }


        return $result;
    }


    /**
     * Получаем строку SQL запроса по группе условий
     *
     * @param $group
     * @return string SQL Where строка
     */
    public function getCollectionWhere($group)
    {
        //По умолчанию верное условие
        if (empty($group['items'])) {
            return 'true';
        }

        $result = array();

        //Перебираем условия
        foreach ($group['items'] as $item) {
            if ($item['type'] == 'group') {
                $result[] = $this->getCollectionWhere($item);
            } else {
                $result[] = $this->getCollectionItemWhere($item);
            }
        }

        $result = implode($group['op'] == 'or' ? ' OR ' : ' AND ', $result);

        if (!empty($result)) {
            $result = '(' . $result . ')';
        }

        return $result;
    }

    /**
     * Получаем строку SQL запроса по одному условию
     *
     * @param $item
     * @return string  SQL Where строка
     */
    public function getCollectionItemWhere($item)
    {
        $result = 'true';
        if (empty($item['op_type'])) {
            return $result;
        }

        $method = 'getCollectionItemWhere_' . ucfirst($item['op_type']);
        if (method_exists($this, $method)) {
            $result = $this->$method($item);
        } else {
            waLog::log('Отсутсвует метод: ' . $method, 'addgifts.log');
        }

        return $result;
    }

    /**
     * Условия выбора товара product
     * @param $item
     * @return string SQL
     */
    public function getCollectionItemWhere_Product($item)
    {
        if (empty($item['product'])) {
            return 'true';
        } else {
            $operation = $item['operation'] == '<>' ? '<>' : '=';
            $id = (int)$item['product'];

            return "p.id $operation $id";
        }
    }

    /**
     * Условия выбора категории category
     * @param $item
     * @return string SQL
     */
    public function getCollectionItemWhere_Category($item)
    {
        if (empty($item['category'])) {
            return 'true';
        }

        $category_id = (int)$item['category'];

        $category_model = new shopCategoryModel();
        $need_category = $category_model->getById($category_id);

        if (empty($need_category)) {
            return 'false';
        }


        //Статичная категория
        if ($need_category['type'] == shopCategoryModel::TYPE_STATIC) {

            if (empty($this->collectionCategoryAlias)) {
                $this->collectionCategoryAlias = $this->collection->addJoin('shop_category_products');
            }

            $alias = $this->collectionCategoryAlias;

            if (!empty($item['with_childs'])) {
                $subcategories = $category_model->descendants($category_id, true)->where('type = ' . shopCategoryModel::TYPE_STATIC)->fetchAll('id');
                $descendant_ids = array_keys($subcategories);
                if ($descendant_ids) {
                    $operation = $item['operation'] == '<>' ? 'NOT IN' : 'IN';
                    return $alias . ".category_id $operation (" . implode(',', $descendant_ids) . ")";
                }
            }

            $operation = $item['operation'] == '<>' ? '<>' : '=';
            return $alias . ".category_id $operation " . $category_id;

        } else {

            //Динамические пока отключены
            return 'false';
        }
    }

    /**
     * Условия выбора список set
     * @param $item
     * @return string SQL
     */
    public function getCollectionItemWhere_Set($item)
    {
        if (empty($item['set'])) {
            return 'true';
        }

        $set_id = $item['set'];

        $operation = ($item['operation'] == '<>') ? '<>' : '=';
        $default_result = ($operation == '=') ? 'false' : 'true';

        $set_model = new shopSetModel();
        $set = $set_model->getById($set_id);

        if (empty($set)) {
            return $default_result;
        }

        //Статичная категория
        if ($set['type'] == shopSetModel::TYPE_STATIC) {

            $ids = array();

            $set_products = new shopSetProductsModel();
            $ids_query = $set_products->select('product_id')->where("set_id = '" . $set_model->escape($set_id) . "'")->query();

            foreach ($ids_query as $set_product_id) {
                $ids[] = $set_product_id['product_id'];
            }

            if (empty($ids)) {
                return $default_result;
            }

            $operation = ($operation == '<>') ? 'NOT IN' : 'IN';
            return "p.id $operation (" . implode(',', $ids) . ")";

        } else {

            //Динамические пока отключены
            return 'false';
        }
    }

    /**
     * Условия выбора по свойствам товара properties
     * @param $item
     * @return string SQL
     */
    public function getCollectionItemWhere_Properties($item)
    {
        if (empty($item['field'])) {
            return 'true';
        }

        $field = $item['field'];

        //Тип товара
        if ($field == 'type_id') {

            if (empty($item['value_type_id'])) {
                return 'true';
            }

            return 'p.type_id ' . ($item['operation_type_id'] == '=' ? '=' : '<>') . ' ' . (int)$item['value_type_id'];
        }

        //Ценовые значения и кол-во
        $operation = $item['operation'];

        //Кол-во на складе
        if ($field == 'count') {

            //Крайний случай бесконечность
            if ($item['value'] == '') {

                switch ($operation) {
                    case '=':
                    case '>=':
                    case '<=':
                        return 'p.count IS NULL';

                    case '>':
                        return 'false';

                    case '<':
                    case '<>':
                        return 'p.count IS NOT NULL';
                }
            } else {

                $value = (int)$item['value'];

                switch ($operation) {
                    case '=':
                        return 'p.count = ' . $value;
                    case '<>':
                        return 'p.count <> ' . $value;

                    case '>=':
                    case '>':
                        return '(p.count ' . $operation . ' ' . $value . ' OR p.count IS NULL)';

                    case '<=':
                    case '<':
                        return '(p.count ' . $operation . ' ' . $value . ' AND p.count IS NOT NULL)';

                }
            }
        }


        if (empty($item['value'])) {
            $item['value'] = 0;
        }

        $value = (float)$item['value'];

        return "$field $operation $value";
    }

}