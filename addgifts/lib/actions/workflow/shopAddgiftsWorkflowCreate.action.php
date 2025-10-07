<?php

/**
 *
 * Для доступа к методу списания со склада
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsWorkflowCreateAction extends shopWorkflowCreateAction
{

    public static function fillItemsStocks(&$items, $virtualstock, $stock){
        return self::fillItemsStockIds($items, $virtualstock, $stock);
    }
}