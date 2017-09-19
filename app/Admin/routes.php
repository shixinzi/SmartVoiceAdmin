<?php

use Illuminate\Routing\Router;

Admin::registerHelpersRoutes();

Route::group([
    'prefix'        => config('admin.prefix'),
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin'],
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->resource('channels', ChannelController::class);
    $router->resource('hdpChannels', HdpChannelController::class);
    $router->resource('programs', ProgramController::class);

    $router->resource('wikis', WikiController::class);
    $router->resource('wikiFollows', WikiFollowController::class);
    $router->resource('wikiFormers', WikiFormerController::class);

    $router->resource('qqAlbums', QQAlbumController::class);
    $router->resource('qqAlbumVideos', QQAlbumVideoController::class);
    $router->resource('qqAlbumHotLogs', QQAlbumHotLogController::class);

});
