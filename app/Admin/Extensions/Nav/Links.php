<?php

namespace App\Admin\Extensions\Nav;

use Encore\Admin\Facades\Admin;

class Links
{
    public function __toString()
    {
    $role = Admin::user()->name;
        return 
            <<<HTML
                <li>
                    <p style="font-weight: bold; margin-top: 15px; color: #fff;">Chức vụ: $role</p>
                </li>
            HTML;
    }
}