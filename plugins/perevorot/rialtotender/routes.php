<?php
Route::group(['middleware' => 'web'], function() {
        Route::get('invoices', ['as' => 'invoices', 'uses' => 'Perevorot\Rialtotender\Controllers\Invoices@get']);
        Route::get('{lang}/invoices', ['as' => 'invoices', 'uses' => 'Perevorot\Rialtotender\Controllers\Invoices@get']);
        Route::get('contract', ['as' => 'contract', 'uses' => 'Perevorot\Rialtotender\Controllers\Contract@get']);
        Route::get('{lang}/contract', ['as' => 'contract', 'uses' => 'Perevorot\Rialtotender\Controllers\Contract@get']);
        Route::get('/git/report', ['as' => 'admin.git_report', 'uses' => 'Perevorot\Rialtotender\Controllers\GitReport@get']);
});