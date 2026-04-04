<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Free for all
    Route::prefix('training')->group(function () {
        Route::get('/simplecalendar', 'App\\Api\\V1\\Controllers\\TrainingCalendarController@getSimpleTrainings');
        Route::get('/simplecalendar/planned', 'App\\Api\\V1\\Controllers\\TrainingCalendarController@getPlannedTrainings');
        Route::get('/upcoming', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTrainings');
        Route::get('/upcoming/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTraining');
        Route::post('{id}/checkinunregistered/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkInUnregistered');
        Route::post('{id}/checkoutunregistered/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkOutUnregistered');
    });

    Route::prefix('branch')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\BranchController@index');
    });

    Route::prefix('faq')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\FaqController@index');
        Route::get('/files', 'App\\Api\\V1\\Controllers\\FaqController@getAllInfoDocuments');
    });

    Route::prefix('notifications')->group(function () {
        Route::post('/subscribe', 'App\\Api\\V1\\Controllers\\NotificationController@subscribe');
    });

    Route::prefix('content')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\ContentController@index');
    });

    Route::prefix('location')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\LocationController@index');
    });

    Route::prefix('group')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\GroupController@index');
        Route::get('/branch/{id}', 'App\\Api\\V1\\Controllers\\GroupController@getByBranchId');
    });

    Route::prefix('trainingSeries')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@index');
    });

    Route::prefix('simpleuser')->group(function () {
        Route::get('/', 'App\\Api\\V1\\Controllers\\SimpleUserController@index');
        Route::post('{id}/storeAbsence', 'App\\Api\\V1\\Controllers\\SimpleUserController@storeAbsence');
        Route::post('{id}/removeAbsence', 'App\\Api\\V1\\Controllers\\SimpleUserController@removeAbsence');
        Route::get('/trainers', 'App\\Api\\V1\\Controllers\\SimpleUserController@getTrainers');
    });

    Route::prefix('auth')->group(function () {
        Route::post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        Route::post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        Route::post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        Route::post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

        Route::post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
        Route::post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
        Route::get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
    });

    Route::middleware('jwt.auth')->group(function () {
        Route::prefix('user')->group(function () {
            Route::get('/', 'App\\Api\\V1\\Controllers\\UserController@index');
            Route::get('/sort', 'App\\Api\\V1\\Controllers\\UserController@getBySort');
            Route::get('/trainer', 'App\\Api\\V1\\Controllers\\UserController@getTrainers');
            Route::get('/birthdays', 'App\\Api\\V1\\Controllers\\UserController@getBirthdayUsers');
            Route::get('/allAbsence', 'App\\Api\\V1\\Controllers\\UserController@getAllAbsenceUsers');
            Route::put('/{id}/removeAbsence', 'App\\Api\\V1\\Controllers\\UserController@removeAbsence');
            Route::delete('{id}', 'App\\Api\\V1\\Controllers\\UserController@destroy');
            Route::put('/me', 'App\\Api\\V1\\Controllers\\UserController@updateMe');
            Route::put('{id}', 'App\\Api\\V1\\Controllers\\UserController@update');
            Route::post('/unregistered', 'App\\Api\\V1\\Controllers\\UserController@createUnregistered');
            Route::post('/me/changepassword', 'App\\Api\\V1\\Controllers\\ChangePasswordController@changePassword');
            Route::post('/me/uploadprofileimage', 'App\\Api\\V1\\Controllers\\ImageController@uploadProfileImage');
        });

        Route::prefix('group')->group(function () {
            Route::post('/', 'App\\Api\\V1\\Controllers\\GroupController@store');
            Route::put('{id}', 'App\\Api\\V1\\Controllers\\GroupController@update');
            Route::delete('{id}', 'App\\Api\\V1\\Controllers\\GroupController@destroy');
        });

        Route::prefix('location')->group(function () {
            Route::post('/', 'App\\Api\\V1\\Controllers\\LocationController@store');
            Route::put('{id}', 'App\\Api\\V1\\Controllers\\LocationController@update');
        });

        Route::prefix('branch')->group(function () {
            Route::post('/', 'App\\Api\\V1\\Controllers\\BranchController@store');
            Route::put('{id}', 'App\\Api\\V1\\Controllers\\BranchController@update');
        });

        Route::prefix('trainingSeries')->group(function () {
            Route::post('/', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@store');
            Route::put('{id}', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@update');
            Route::delete('{id}', 'App\\Api\\V1\\Controllers\\TrainingSeriesController@destroy');
        });

        Route::prefix('trainingevaluation')->group(function () {
            Route::get('/accountingtimestatistics', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@getAccountingTimeStatistics');
            Route::get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@getPastTrainingsForTrainer');
            Route::post('/exportaccountingtimes', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@exportAccountingTimes');
            Route::post('{id}/removeparticipant/{userId}', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@removeParticipant');
            Route::post('{id}/addparticipant/{userId}', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@addParticipant');
            Route::post('{id}/evaluated', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@trainingEvaluated');
            Route::post('{id}/updateaccountingtime', 'App\\Api\\V1\\Controllers\\TrainingEvaluationController@updateAccountingTime');
        });

        Route::prefix('trainingprepare')->group(function () {
            Route::get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@getUpcomingTrainingsForTrainer');
            Route::post('{id}/prepared', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@trainingEvaluated');
            Route::post('{id}/updatetrainingtime', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateTrainingTime');
            Route::post('{id}/updatelocation', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateLocation');
            Route::post('{id}/updatecomment', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateComment');
            Route::post('{id}/updatecontent', 'App\\Api\\V1\\Controllers\\TrainingPrepareController@updateContent');
        });

        Route::prefix('training')->group(function () {
            Route::get('/participationcount', 'App\\Api\\V1\\Controllers\\TrainingController@getParticipationCount');
            Route::get('/calendar', 'App\\Api\\V1\\Controllers\\TrainingCalendarController@getTrainings');
            Route::get('/', 'App\\Api\\V1\\Controllers\\TrainingController@index');
            Route::get('/sort', 'App\\Api\\V1\\Controllers\\TrainingController@getBySort');
            Route::get('/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getById');
            Route::post('/', 'App\\Api\\V1\\Controllers\\TrainingController@store');
            Route::post('{id}/checkin/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkIn');
            Route::post('{id}/checkout/{userId}', 'App\\Api\\V1\\Controllers\\TrainingController@checkOut');
            Route::post('{id}/prepared', 'App\\Api\\V1\\Controllers\\TrainingController@trainingPrepared');
            Route::patch('{id}/deleteinfuture', 'App\\Api\\V1\\Controllers\\TrainingController@deleteinfuture');
            Route::put('{id}', 'App\\Api\\V1\\Controllers\\TrainingController@update');
            Route::get('{id}/trainingscount/{year}', 'App\\Api\\V1\\Controllers\\TrainingController@getTrainingTimeline');
            Route::get('/upcoming/trainer/{id}', 'App\\Api\\V1\\Controllers\\TrainingController@getUpcomingTrainingsForUser');
            Route::delete('{id}', 'App\\Api\\V1\\Controllers\\TrainingController@destroy');
        });

        Route::get('refresh', function () {
            return response()->json([
                'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
            ]);
        })->middleware('jwt.refresh');
    });

    Route::get('/hello', function () {
        return 'Hello World!';
    });
    Route::get('/mailtest', function () {
        \Illuminate\Support\Facades\Mail::raw('Test Mail', function ($message) {
            $message->to('bindersebastian@online.de');
        });
    });
});
