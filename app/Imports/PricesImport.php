<?php

namespace App\Imports;

use App\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;

class PricesImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return User|null
     */
    public function model(array $row)
    {
        return new User([
           'province'     => $row[1],
           'district'     => $row[2],
           'street'       => $row[3],
           'from'         => $row[4],
           'to'           => $row[5],
           'location'     => $row[6],
           'type'         => $row[7],
           'from_price'   => $row[8],
           'to_price'     => $row[9],
           'note'         => $row[10],
        ]);
    }
}