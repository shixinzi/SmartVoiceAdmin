<?php

namespace App\Admin\Controllers;

use App\Models\QQAlbum;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class QQAlbumController extends Controller
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

            $content->header('腾讯视频管理');
            $content->description('影视列表');

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

            $content->header('腾讯视频管理');
            $content->description('添加影视');

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

            $content->header('腾讯视频管理');
            $content->description('添加影视');

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
        return Admin::grid(QQalbum::class, function (Grid $grid) {
            $grid->model()->orderBy('hot_num', 'desc');
            $grid->column('album_verpic', '封面')->display(function($album_verpic) {
                return "<img src='$album_verpic' width='100px'>";
            });
            $grid->column('album_name', '名称')->display(function() {
                $name = $this->album_name."[".$this->type."]";
                $name .= $this->en_name ? "<br>".$this->en_name : "";
                $name .= $this->alias_name ? "<br>".$this->alias_name : "";
                return $name;
            });
            $grid->column('genre', '类型')->display(function() {
                return $this->genre ."<br>". $this->sub_genre;
            });
            $grid->column('director', '演职员')->display(function() {
                $name = [];
                if($this->director) {
                    array_push($name, $this->director);
                }
                if($this->actor) {
                    array_push($name, $this->actor);
                }
                if($this->guests) {
                    array_push($name, $this->guests);
                }
                return implode("<br>", $name);
            });
            $grid->updated_at('更新时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(QQalbum::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
