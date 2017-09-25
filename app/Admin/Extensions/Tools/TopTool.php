<?php

namespace  App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class TopTool
{
    public function script()
    {
        $url = Request::fullUrlWithQuery(['istop' => '_istop_']);

        return '';
    }

    public function render()
    {
        //Admin::script($this->script());

        $options = [
            'all'   => 'All',
            'm'     => 'Male',
            'f'     => 'Female',
        ];

        return view('admin.tools.istop', compact('options'));
    }
}