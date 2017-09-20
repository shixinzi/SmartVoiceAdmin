<?php

namespace App\Admin\Controllers;

use App\Models\HdpChannel;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class HdpChannelController extends Controller
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

            $content->header('Hdp频道管理');
            $content->description('频道列表');

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

            $content->header('Hdp频道管理');
            $content->description('频道编辑');

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

            $content->header('Hdp频道管理');
            $content->description('频道新增');

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
        return Admin::grid(HdpChannel::class, function (Grid $grid) {

            $grid->model()->orderBy('num', 'asc');
            $grid->column('name', '名称');
            $grid->column('num', '频道号');
            $grid->column('type', '频道类型');
            $grid->column('channel_code', 'Code');
            $grid->created_at();
            $grid->updated_at();

            $grid->filter(function($filter){
                $filter->disableIdFilter();
                $filter->like('name', '名称');
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
        return Admin::form(HdpChannel::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
