<?php

/**
 *
 * Загрузка изображений в настройках
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginSettingsUploadController extends waUploadJsonController
{

    public $url;

    protected function process()
    {
        $this->processFile(waRequest::file('file'));
    }

    protected function isValid($f)
    {
        try {
            $f->waImage();
        } catch (Exception $e) {
            $this->errors[] = 'Разрешаются только изображения';
            return false;
        }
        return true;
    }

    protected function getPath()
    {
        $this->url = wa()->getDataUrl('plugins/addgifts/upload/', true, 'shop');
        return wa()->getDataPath('plugins/addgifts/upload/', true, 'shop');
    }

    protected function save(waRequestFile $f)
    {
        $this->name = md5($f->size . $f->name) . '.' . $f->extension;
        $this->response['url'] = $this->url . $this->name;
        return $f->moveTo($this->path, $this->name);
    }
}