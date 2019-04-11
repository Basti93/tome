<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {

    //free for all
    $api->group(['prefix' => 'training'], function(Router $api) {
        $api->get('/upcoming', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTrainings');
        $api->get('/automaticattendservice', 'App\\Api\\V1\\Controllers\\TrainingController@automaticAttendService');
        $api->post('{id}/checkinunregistered/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkInUnregistered');
        $api->post('{id}/checkoutunregistered/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkOutUnregistered');
    });

    $api->group(['prefix' => 'branch'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\BranchController@index');
    });

    $api->group(['prefix' => 'content'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\ContentController@index');
    });

    $api->group(['prefix' => 'location'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\LocationController@index');
    });

    $api->group(['prefix' => 'group'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\GroupController@index');
        $api->get('/branch/{id}', 'App\\Api\\V1\\Controllers\\GroupController@getByBranchId');
    });

    $api->group(['prefix' => 'simpleuser'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\SimpleUserController@index');
    });

    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

        $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
        $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
        $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
    });

    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        Route::resource('roles','RoleController');
        Route::resource('users','UserController');
        Route::resource('trainings','TrainingController');

        $api->group(['prefix' => 'user'], function(Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\UserController@index');
            $api->get('/sort', 'App\\Api\\V1\\Controllers\\UserController@getBySort');
            $api->get('/trainer', 'App\\Api\\V1\\Controllers\\UserController@getTrainers');
            $api->get('/nonapproved', 'App\\Api\\V1\\Controllers\\UserController@getNonapproved');
            $api->get('/nonregistered', 'App\\Api\\V1\\Controllers\\UserController@getNonRegistered');
            $api->get('/nonapprovedcount', 'App\\Api\\V1\\Controllers\\UserController@getNonApprovedCount');
            $api->delete('{id}', 'App\\Api\\V1\\Controllers\\UserController@destroy');
            $api->put('/me', 'App\\Api\\V1\\Controllers\\UserController@updateMe');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\UserController@update');
            $api->post('/unregistered', 'App\\Api\\V1\\Controllers\\UserController@createUnregistered');
            $api->post('/{id}/approve', 'App\\Api\\V1\\Controllers\\UserController@approveUser');
        });

        $api->group(['prefix' => 'training'], function(Router $api) {
            $api->get('/participationcount', 'App\\Api\\V1\\Controllers\\TrainingController@getParticipationCount');
            $api->get('/', 'App\\Api\\V1\\Controllers\\TrainingController@index');
            $api->get('/sort', 'App\\Api\\V1\\Controllers\\TrainingController@getBySort');
            $api->get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getById');
            $api->post('/', 'App\\Api\\V1\\Controllers\\TrainingController@store');
            $api->post('{id}/checkin/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkIn');
            $api->post('{id}/checkout/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkOut');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\TrainingController@update');
            $api->get('{id}/trainingscount/{year}', 'App\\Api\\V1\\Controllers\\TrainingController@getTrainingTimeline');
        });

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function() {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

});
