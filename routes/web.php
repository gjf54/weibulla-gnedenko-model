<?php

use App\Core\Router;

Router::group('/simulation', function() {
    Router::get('/', 'SimulationController@index');
    Router::post('/generate', 'SimulationController@generate');
    Router::post('/step', 'SimulationController@step');
    Router::post('/auto-step', 'SimulationController@autoStep');
    Router::post('/reset', 'SimulationController@reset');
    Router::get('/state', 'SimulationController@getState');
    Router::post('/clear-data', 'SimulationController@clearData');
});

Router::group('/statistics', function() {
    Router::get('/', 'StatisticsController@index');
    Router::get('/sessions', 'StatisticsController@getSessions');
    Router::get('/session/{id}', 'StatisticsController@getSession');
    Router::get('/session/{id}/steps', 'StatisticsController@getSessionSteps');
    Router::get('/session/{id}/history', 'StatisticsController@getSessionHistory');
    Router::delete('/session/{id}', 'StatisticsController@deleteSession');
    Router::get('/dashboard', 'StatisticsController@dashboard');
    Router::get('/correlations', 'StatisticsController@correlations');
    Router::get('/export/csv', 'StatisticsController@exportCsv');
});

Router::get('/', function() {
    return App\Core\Response::redirect('/simulation');
});