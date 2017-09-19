<?php

namespace App\Admin\Controllers;

use App\Models\Program;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class ProgramController extends Controller
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

            $content->header('节目单管理');
            $content->description('节目单列表');

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

            $content->header('节目单管理');
            $content->description('节目单编辑');

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

            $content->header('节目单管理');
            $content->description('节目单创建');

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
        return Admin::grid(Program::class, function (Grid $grid) {
            $grid->model()->orderBy('date', 'desc');
            $grid->column('name', '节目名称');
            $grid->column('start_time', '开始时间');
            $grid->column('wiki_id', '关联wiki');
            $grid->column('tags', '节目标签')->display(function ($tags) {
                return $tags ? implode(";", array_slice($tags, 0, 3)) : '';
            });

            $grid->updated_at('更新时间');

            $grid->filter(function ($filter) {

                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->equal('channel_code', '频道');
                $filter->equal('date', '日期');

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
        return Admin::form(Program::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
