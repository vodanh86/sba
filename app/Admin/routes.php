<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::resource('admin/auth/users', \App\Admin\Controllers\CustomUserController::class)->middleware(config('admin.route.middleware'));

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/print-contract', 'WordController@createContract');
    $router->get('/print-invitation-letter', 'WordController@createInvitationLetter');
    $router->get('/print-official-assessment', 'WordController@createOfficialAssessment');
    $router->get('/print-contract-acceptance', 'WordController@createContractAcceptance');
    $router->get('/', 'HomeController@index')->name('home');
    $router->get('/sale-reports', 'ReportController@saleReport');
    $router->get('/ba-reports', 'ReportController@baReport');
    $router->get('/supervisor-reports', 'ReportController@supervisorReport');
    $router->get('/ba-manager-reports', 'ReportController@baManagerReport');
    $router->get('/accountant-reports', 'ReportController@accountantManagerReport');
    $router->get('/price-upload', 'UploadController@index');
    $router->resource('branches', BranchController::class);
    $router->resource('individual-customers', IndividualCustomerController::class);
    $router->resource('business-customers', BusinessCustomerController::class);
    $router->resource('invitation-letters', InvitationLetterController::class);
    $router->resource('contracts', ContractController::class);
    $router->resource('assigned-contracts', AssignedContractController::class);
    $router->resource('statuses', StatusController::class);
    $router->resource('status-transitions', StatusTransitionController::class);
    $router->resource('task-notes', TaskNoteController::class);
    $router->resource('pre-assessments', PreAssessmentController::class);
    $router->resource('official-assessments', OfficialAssessmentController::class);
    $router->resource('score-cards', ScoreCardController::class);
    $router->resource('contract-acceptances', ContractAcceptanceController::class);
    $router->resource('valuation-documents', ValuationDocumentController::class);
    $router->resource('done-contracts', DoneContractController::class);
    $router->resource('done-invitation-letters', DoneInvitationLettersController::class);
    $router->resource('notifications', NotificationController::class);
});
