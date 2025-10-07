<?php

/**
 * расширение: Мультивитринность
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitTemplates
{

    public function getThemes()
    {
        $result = array();
        $themes = wa()->getThemes('shop');
        foreach ($themes as $theme) {
            $result[] = array('id' => $theme->id, 'name' => $theme->getName());
        }
        return $result;
    }


    public function loadTemplates($list = false)
    {
        $templates = include($this->path . '/lib/config/templates.php');

        //Одноуровневый список
        if ($list) {
            $result = array();
            foreach ($templates as $group) {
                foreach ($group['templates'] as $template) {
                    $result[$template['filename']] = $template;
                }
            }
            return $result;
        }

        return $templates;
    }

    public function getDefaultPathForTemplate($template, $theme_id)
    {

        $result = (!empty($template['default'])) ? $template['default'] : '/templates/actions/frontend/' . $template['filename'];
        $result = str_replace('[+theme_id+]', $theme_id, $result);

        if (file_exists($this->path . $result)) {
            $result = $this->path . $result;
        } else {
            $result = '';
        }
        return $result;
    }

    public function getDefaultUrlForTemplate($template, $theme_id)
    {

        $result = (!empty($template['default'])) ? $template['default'] : '/templates/actions/frontend/' . $template['filename'];
        $result = str_replace('[+theme_id+]', $theme_id, $result);

        if (file_exists($this->path . $result)) {
            $result = substr($this->getPluginStaticUrl(), 0, -1) . $result;
        } else {
            $result = '';
        }
        return $result;
    }

    public function getDefaultForTemplate($template, $theme_id)
    {
        $path = $this->getDefaultPathForTemplate($template, $theme_id);

        return !empty($path) ? file_get_contents($path) : '';
    }

    public function getTemplates()
    {

        $result = array('themes' => $this->getThemes());

        $themes = $this->getThemes();

        foreach ($themes as $key => $theme) {
            $themes[$key] = new waTheme($theme['id']);
        }

        $templates = $this->loadTemplates();

        $overwrites = array();
        $defaults = array();


        //Перебираем темы
        foreach ($themes as $theme) {

            $overwrites[$theme->id] = array();
            $defaults[$theme->id] = array();

            $theme_path = $theme->getPath();

            //Перебираем шаблоны
            foreach ($templates as $group) {
                foreach ($group['templates'] as $template) {

                    //Значение шаблона по умолчанию в зависимости от темы дизайна
                    $default = $this->getDefaultForTemplate($template, $theme->id);

                    $defaults[$theme->id][$template['filename']] = $default;

                    if (file_exists($theme_path . '/addgifts__' . $template['filename'])) {
                        if (!isset($overwrites[$theme->id])) {
                            $overwrites[$theme->id] = array();
                        }
                        $overwrites[$theme->id][$template['filename']] = file_get_contents($theme_path . '/addgifts__' . $template['filename']);
                    }
                }
            }
        }

        $result['templates'] = $templates;
        $result['overwrites'] = $overwrites;
        $result['defaults'] = $defaults;

        return $result;
    }


    public function saveTemplates($overwrite_templates)
    {

        $templates = $this->loadTemplates(true);

        foreach ($overwrite_templates as $theme_id => $overwrite_tmpls) {

            $theme = new waTheme($theme_id);

            foreach ($overwrite_tmpls as $filename => $overwrite_template) {

                $default = $this->getDefaultForTemplate($templates[$filename], $theme_id);

                $theme_filename = $this->id . '__' . $filename;

                $theme_filepath = $theme->getPath() . '/' . $theme_filename;

                //Сохраняем изменения в тему
                if ($default != $overwrite_template) {


                    $description = $this->info['name'] . ': ' . $templates[$filename]['name'];

                    if (file_exists($theme_filepath)) {
                        $theme->changeFile($theme_filename, $description);
                    } else {
                        $theme->addFile($theme_filename, $description);
                    }

                    file_put_contents($theme_filepath, $overwrite_template);

                } else {
                    $theme->removeFile($theme_filename);
                }

            }


        }

    }

    public function getTemplateUrl($template, $theme_id = null)
    {
        if (is_null($theme_id)) {
            $theme_id = waRequest::getTheme();
        }

        $theme = new waTheme($theme_id);
        $filepath = $theme->getPath() . '/' . $this->id . '__' . $template;


        //В теме дизайна нет файла
        if (!is_readable($filepath)) {
            $templates = $this->loadTemplates(true);
            $a_template = $templates[$template];
            $filepath = $this->getDefaultUrlForTemplate($a_template, $theme_id);
        } else {
            $filepath = substr($theme->getUrl(), 0, -1) . '/' . $this->id . '__' . $template;
        }

        return $filepath;
    }

    public function getTemplatePath($template, $theme_id = null)
    {
        if (is_null($theme_id)) {
            $theme_id = waRequest::getTheme();
        }

        $theme = new waTheme($theme_id);
        $filepath = $theme->getPath() . '/' . $this->id . '__' . $template;

        //В теме дизайна нет файла
        if (!is_readable($filepath)) {
            $templates = $this->loadTemplates(true);
            $a_template = $templates[$template];
            $filepath = $this->getDefaultPathForTemplate($a_template, $theme_id);
        }

        return $filepath;
    }

    /**
     * Парсер шаблона в зависимости от темы
     * @param $template
     * @param array $variables
     * @param null $storefront_settings
     * @param null $theme_id
     * @return string
     */
    public function parseTemplate($template, $variables = array(), $storefront_settings = null, $theme_id = null)
    {

        if (is_null($storefront_settings)) {
            $storefront_settings = $this->getStorefrontSettings();
        }

        if (is_null($theme_id)) {
            $theme_id = waRequest::getTheme();
        }


        $view = wa()->getView();

        //Основные переменные
        $view->assign('settings', $storefront_settings);
        $view->assign('common_settings', $this->getSettings());

        $view->assign('current_theme', $theme_id);
        $view->assign('base_url', wa('shop')->getRouteUrl('shop/frontend'));

        //Дополнительные переменные
        if (!empty($variables)) {
            foreach ($variables as $name => $value) {
                $view->assign($name, $value);
            }
        }

        $this->additionalParseTemplate($view, $theme_id);

        $filepath = $this->getTemplatePath($template, $theme_id);

        return $view->fetch($filepath);
    }
}