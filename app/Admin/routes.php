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

    $router->get('/', 'HomeController@index')->name('home');
    $router->get('/assigned-contracts', 'ContractController@assignedContracts');
    $router->get('/sale-reports', 'ReportController@saleReport');
    $router->resource('branches', BranchController::class);
    $router->resource('individual-customers', IndividualCustomerController::class);
    $router->resource('business-customers', BusinessCustomerController::class);
    $router->resource('invitation-letters', InvitationLetterController::class);
    $router->resource('contracts', ContractController::class);
    $router->resource('statuses', StatusController::class);
    $router->resource('status-transitions', StatusTransitionController::class);
    $router->resource('task-notes', TaskNoteController::class);
    $router->resource('pre-assessments', PreAssessmentController::class);
    $router->resource('official-assessments', OfficialAssessmentController::class);
    $router->resource('score-cards', ScoreCardController::class);
    $router->resource('contract-acceptances', ContractAcceptanceController::class);
    $router->resource('valuation-documents', ValuationDocumentController::class);
    $router->resource('done-contracts', DoneContractController::class);
});
