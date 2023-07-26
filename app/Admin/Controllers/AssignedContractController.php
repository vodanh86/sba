<?php

namespace App\Admin\Controllers;

class AssignedContractController extends ContractController
{
    protected $title = 'Hợp đồng được giao';

    protected function grid()
    {
        return $this->search(1);
    }
}