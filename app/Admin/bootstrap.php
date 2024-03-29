<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */
Use Encore\Admin\Admin;
use Encore\Admin\Facades\Admin as AdminFacade;

Encore\Admin\Form::forget(['map', 'editor']);
Admin::css(env('APP_URL').'/css/main.css');
Admin::css("https://skywalkapps.github.io/bootstrap-notifications/stylesheets/bootstrap-notifications.css");
Admin::js(env('APP_URL').'/js/pusher.min.js');
Admin::favicon(env('APP_URL').'/favicon.ico');
AdminFacade::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {
    $navbar->right(new \App\Admin\Extensions\Nav\Links());
});
