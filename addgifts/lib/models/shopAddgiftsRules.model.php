<?php

/**
 * Модель для работы с правилами
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsRulesModel extends waModel
{
    protected $table = 'shop_addgifts__rules';

    public static $_cache_get_rules = array();

    public static $_cache_get_product_rules = null;

    public static $_cache_get_cart_rules = null;


    public function updateStatus($ids, $status)
    {
        if (empty($ids)) {
            return;
        }
        $sql = 'update ' . $this->table . ' set `status`=' . (int)$status . ' where id in (' . implode(',', $ids) . ')';
        $this->query($sql);
    }

    public static function decodeRule($rule)
    {
        $decoded_rule = json_decode($rule['rule'], true);
        $decoded_rule['id'] = $rule['id'];
        $decoded_rule['status'] = $rule['status'];
        $decoded_rule['sort'] = $rule['sort'];
        $decoded_rule['is_cart_sum'] = (int)$rule['is_cart_sum'];

        return $decoded_rule;
    }


    public function loadRules($only_active = false)
    {
        $builder = $this->order('sort asc');
        if ($only_active) {
            $builder = $builder->where('status = 1');
        }
        $rules = $builder->fetchAll();

        $result = array();

        foreach ($rules as $key => $rule) {
            $result[] = self::decodeRule($rule);
        }
        return $result;
    }


    public static function getRules($get_cart_rules = false)
    {

        $_cache_key = (int)$get_cart_rules;

        if (isset(self::$_cache_get_rules[$_cache_key])) {
            return self::$_cache_get_rules[$_cache_key];
        }

        $model = new self();

        $model = $model->order('sort asc');
        $model = $model->where('status = 1');
        $model = $model->where('is_cart_sum = ' . ($get_cart_rules ? 1 : 0));
        $rules = $model->fetchAll();

        $result = array();

        foreach ($rules as $key => $rule) {
            $result[] = self::decodeRule($rule);
        }

        self::$_cache_get_rules[$_cache_key] = $result;

        return $result;
    }

    public static function getProductRules()
    {

        if (!is_null(self::$_cache_get_product_rules)) {
            return self::$_cache_get_product_rules;
        }

        $model = new self();

        $model = $model->order('sort asc');
        $model = $model->where('status = 1 and is_cart_sum = 0');
        $rules = $model->fetchAll();

        $result = array();

        foreach ($rules as $key => $rule) {
            $result[] = self::decodeRule($rule);
        }

        self::$_cache_get_product_rules = $result;

        return $result;
    }

    public static function getCartRules()
    {

        if (!is_null(self::$_cache_get_cart_rules)) {
            return self::$_cache_get_cart_rules;
        }

        $model = new self();

        $model = $model->order('sort asc');
        $model = $model->where('status = 1 and is_cart_sum = 1');
        $rules = $model->fetchAll();

        $result = array();

        foreach ($rules as $key => $rule) {
            $result[] = self::decodeRule($rule);
        }

        self::$_cache_get_cart_rules = $result;

        return $result;
    }

    public static function getRule($id)
    {
        $model = new self();

        $data = $model->getById($id);

        if (!empty($data)) {
            $data = self::decodeRule($data);
        }

        return $data;
    }

    public function save($rule)
    {
        if (empty($rule['sort'])) {
            $sort = $this->order('sort desc')->fetchField('sort') + 1;
        } else {
            $sort = (int)$rule['sort'];
        }

        $data = array('status' => (int)$rule['status'], 'sort' => $sort, 'is_cart_sum' => (int)$rule['is_cart_sum']);
        unset($rule['status'], $rule['sort'], $rule['is_cart_sum']);

        $data['rule'] = json_encode($rule);

        if (empty($rule['id'])) {
            $this->insert($data);
        } else {
            $this->updateById($rule['id'], $data);
        }
    }

    public function resortRules($id, $direction)
    {

        $rules = $this->order('sort asc')->fetchAll();

        $sort = array();

        foreach ($rules as $rule) {
            $sort[] = $rule['id'];
        }

        foreach ($sort as $key => $value) {
            if ($value == $id) {
                $swap = $sort[$key + $direction];
                $sort[$key + $direction] = $value;
                $sort[$key] = $swap;
                break;
            }
        }

        foreach ($sort as $key => $value) {
            $this->updateById($value, array('sort' => $key + 1));
        }
    }
}