<?php


//Обнволение 2.0
$this->updateTo20();

//Удаление файлов не используемых в версии 2.0
$files = array(
    '/css/multi_products.css',
    '/css/multi_products.min.css',
    '/css/select2.min.css',
    '/css/settings.css',
    '/css/settings.min.css',
    '/js/i18n/',
    '/js/multi_products.js',
    '/js/multi_products.min.js',
    '/js/select2.min.js',
    '/js/settings.js',
    '/js/settings.min.js',
    '/lib/actions/elements/',
    '/lib/shopAddgiftsPluginBackend.actions.php',
    '/lib/shopAddgiftsPluginBackend.controller.php',
    '/lib/shopAddgiftsPluginFrontend.actions.php',
    '/lib/shopAddgiftsPluginSettings.actions.php',
    '/lib/shopAddgiftsWorkflowCreate.action.php',
    '/lib/classes/shopAddgiftsHelper.class.php',
    '/locale/',
    '/template/actions/backend/elements/',
    '/template/actions/backend/backendCategoryDialog.html',
    '/template/actions/backend/backendProductEdit.html',
    '/template/actions/backend/BackendSelect.html',
    '/template/actions/frontend/template.html',
    '/template/actions/frontend/template_badge.html',
    '/template/actions/frontend/template_gift.html',
    '/template/actions/frontend/template_product.html',
    '/template/actions/frontend/template_self.html',
    '/template/actions/frontend/template_separate.html',
    '/template/actions/frontend/template_separate_self.html',
    '/template/actions/settings/element/',
    '/template/actions/settings/SettingsDefault.html',
    '/template/actions/settings/SettingsRoute.html',
    '/template/actions/settings/SettingsUpload.html',
);

foreach ($files as $file) {
    waFiles::delete($this->path . $file, true);
}
