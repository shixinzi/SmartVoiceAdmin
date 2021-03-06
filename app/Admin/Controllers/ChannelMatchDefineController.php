<?php

namespace App\Admin\Controllers;

use App\Models\ChannelMatchDefine;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class ChannelMatchDefineController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('频道匹配管理');
            $content->description('匹配列表');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('频道匹配管理');
            $content->description('新建匹配规则');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('频道匹配管理');
            $content->description('新建匹配规则');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(ChannelMatchDefine::class, function (Grid $grid) {

            $grid->column('channel_name', '名称');
            $grid->column('channel_code', 'Code');
            $grid->column('sp', 'SP');
            $grid->created_at();
            $grid->updated_at();

            $grid->filter(function($filter){
                $filter->disableIdFilter();
                $filter->like('channel_name', '名称');
                $filter->equal('channel_code', 'Code');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(ChannelMatchDefine::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('channel_name', '名称');
            $form->text('channel_code', 'Code');
            $form->text('sp', 'SP');
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
