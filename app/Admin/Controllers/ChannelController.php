<?php

namespace App\Admin\Controllers;

use App\Models\Channel;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\Admin\Extensions\Tools\TopTool;

class ChannelController extends Controller
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

            $content->header('频道管理');
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

            $content->header('频道管理');
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

            $content->header('频道管理');
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
        return Admin::grid(Channel::class, function (Grid $grid) {
            $grid->model()->orderBy('hot', 'desc');
            $grid->column('name', '名称');
            $grid->column('code', 'Code');
            $grid->column('logo', 'Logo')->display(function($logo) {
                return '<img src="'.$logo.'" width="100px;">';
            });
            $grid->column('tags', '类型')->display(function($tags) {
                return implode(';', $tags);
            });
            $grid->column('hot', '热度');
            $grid->column('istop', '推荐')->switch([
                'on' => ['text' => 'Y'],
                'off' => ['text' => 'N'],
            ]);
            //$grid->created_at();
            $grid->updated_at('最后更新');

            $grid->filter(function($filter){
                $filter->disableIdFilter();
                $filter->like('name', '名称');
                $filter->equal('code', 'Code');
                $filter->is('tags', '分类')->select(['cctv' => 'cctv', 'tv' => 'tv']);
                //$filter->equal('istop', '推荐')->select([0 => 'No' ,1 => 'Yes']);
            });

            $grid->tools(function ($tools) {
                //$tools->append(new RefreshTimer(5000));
                //$tools->append(new TopTool());
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
        return Admin::form(Channel::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->switch('istop','是否推荐');
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
