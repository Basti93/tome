<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {

    //free for all
    $api->group(['prefix' => 'training'], function(Router $api) {
        $api->get('/simplecalendar', 'App\\Api\\V1\\Controllers\\TrainingCalendarController@getSimpleTrainings');
        $api->get('/simplecalendar/planned', 'App\\Api\\V1\\Controllers\\TrainingCalendarController@getPlannedTrainings');
        $api->get('/upcoming', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTrainings');
        $api->get('/upcoming/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTraining');
        $api->post('{id}/checkinunregistered/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkInUnregistered');
        $api->post('{id}/checkoutunregistered/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkOutUnregistered');
    });

    $api->group(['prefix' => 'branch'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\BranchController@index');
    });

    $api->group(['prefix' => 'faq'], function(Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\FaqController@index');
        $api->get('/files', 'App\\Api\\V1\\Controllers\\FaqController@getAllInfoDocuments');
    });

    $api->group(['prefix' => 'notifications'], function(Router $api) {
        $api->post('/subscribe', 'App\\Api\\V1\\Controllers\\NotificationController@subscribe');
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
        $api->get('/trainers', 'App\\Api\\V1\\Controllers\\SimpleUserController@getTrainers');
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
            $api->get('/birthdays', 'App\\Api\\V1\\Controllers\\UserController@getBirthdayUsers');
            $api->delete('{id}', 'App\\Api\\V1\\Controllers\\UserController@destroy');
            $api->put('/me', 'App\\Api\\V1\\Controllers\\UserController@updateMe');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\UserController@update');
            $api->post('/unregistered', 'App\\Api\\V1\\Controllers\\UserController@createUnregistered');
            $api->post('/{id}/approve', 'App\\Api\\V1\\Controllers\\UserController@approveUser');
            $api->post('/me/changepassword', 'App\\Api\\V1\\Controllers\\ChangePasswordController@changePassword');
            $api->post('/me/uploadprofileimage', 'App\\Api\\V1\\Controllers\\ImageController@uploadProfileImage');
        });

        $api->group(['prefix' => 'group'], function(Router $api) {
            $api->post('/', 'App\\Api\\V1\\Controllers\\GroupController@store');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\GroupController@update');
            $api->delete('{id}', 'App\\Api\\V1\\Controllers\\GroupController@destroy');
        });

        $api->group(['prefix' => 'location'], function(Router $api) {
            $api->post('/', 'App\\Api\\V1\\Controllers\\LocationController@store');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\LocationController@update');
        });

        $api->group(['prefix' => 'branch'], function(Router $api) {
            $api->post('/', 'App\\Api\\V1\\Controllers\\BranchController@store');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\BranchController@update');
        });

        $api->group(['prefix' => 'trainingSeries'], function(Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@index');
            $api->post('/', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@store');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@update');
            $api->delete('{id}', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@destroy');
        });

        $api->group(['prefix' => 'trainingevaluation'], function(Router $api) {
            $api->get('/accountingtimestatistics', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@getAccountingTimeStatistics');
            $api->get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@getPastTrainingsForTrainer');
            $api->post('/exportaccountingtimes', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@exportAccountingTimes');
            $api->post('{id}/removeparticipant/{userId}', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@removeParticipant');
            $api->post('{id}/addparticipant/{userId}', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@addParticipant');
            $api->post('{id}/evaluated', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@trainingEvaluated');
            $api->post('{id}/updateaccountingtime', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@updateAccountingTime');
        });

        $api->group(['prefix' => 'trainingprepare'], function(Router $api) {
            $api->get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@getUpcomingTrainingsForTrainer');
            $api->post('{id}/prepared', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@trainingEvaluated');
            $api->post('{id}/updatetrainingtime', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateTrainingTime');
            $api->post('{id}/updatelocation', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateLocation');
            $api->post('{id}/updatecomment', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateComment');
            $api->post('{id}/updatecontent', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateContent');
        });

        $api->group(['prefix' => 'training'], function(Router $api) {
            $api->get('/participationcount', 'App\\Api\\V1\\Controllers\\TrainingController@getParticipationCount');
            $api->get('/calendar', 'App\\Api\\V1\\Controllers\\TrainingCalendarController@getTrainings');
            $api->get('/', 'App\\Api\\V1\\Controllers\\TrainingController@index');
            $api->get('/sort', 'App\\Api\\V1\\Controllers\\TrainingController@getBySort');
            $api->get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getById');
            $api->post('/', 'App\\Api\\V1\\Controllers\\TrainingController@store');
            $api->post('{id}/checkin/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkIn');
            $api->post('{id}/checkout/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkOut');
            $api->post('{id}/prepared', 'App\\Api\\V1\\Controllers\\TrainingController@trainingPrepared');
            $api->put('{id}', 'App\\Api\\V1\\Controllers\\TrainingController@update');
            $api->get('{id}/trainingscount/{year}', 'App\\Api\\V1\\Controllers\\TrainingController@getTrainingTimeline');
            $api->get('/upcoming/trainer/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTrainingsForUser');

            $api->delete('{id}', 'App\\Api\\V1\\Controllers\\TrainingController@destroy');
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

    $api->get('/hello',function(){
        return 'Hello World!';
    });
    $api->get('/mailtest',function(){
        \Illuminate\Support\Facades\Mail::raw('Test Mail', function ($message){
            $message->to('bindersebastian@online.de');
        });
    });

});
