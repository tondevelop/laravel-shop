<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\CrowdfundingProduct;
use App\Models\Product;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CrowdfundingProductsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '众筹商品';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product());

        // 只展示 type 为众筹类型的商品
        $grid->model()->where('type', Product::TYPE_CROWDFUNDING);
        $grid->column('id', __('编号'))->sortable();
        $grid->column('title', __('商品名称'));
        $grid->column('on_sale', __('已上架'))->display(
            function ($value) {
                return $value ? '是' : '否';
            }
        );
        $grid->column('price', __('价格'));
        // 展示众筹相关字段
        $grid->column('crowdfunding.target_amount', __('目标金额'));
        $grid->column('crowdfunding.end_at', __('结束时间'));
        $grid->column('crowdfunding.total_amount', __('目前金额'));
        $grid->column('crowdfunding.status', __('状态'))->display(
            function ($value) {
                return CrowdfundingProduct::$statusMap[$value];
            }
        );

        $grid->actions(
            function ($actions) {
                $actions->disableView();
                $actions->disableDelete();
            }
        );

        $grid->tools(
            function ($tools) {
                $tools->batch(
                    function ($batch) {
                        $batch->disableDelete();
                    }
                );
            }
        );

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Product::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('category_id', __('Category id'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('image', __('Image'));
        $show->field('on_sale', __('On sale'));
        $show->field('rating', __('Rating'));
        $show->field('sold_count', __('Sold count'));
        $show->field('review_count', __('Review count'));
        $show->field('price', __('Price'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Product());

        // 在表单中添加一个名为 type，为 Product::TYPE_CROWDFUNDING 的隐藏字段
        $form->hidden('type')->value(Product::TYPE_CROWDFUNDING);
        $form->text('title', __('商品名称'));
        $form->select('category_id', '类目')->options(
            function ($id) {
                $category = Category::find($id);
                if ($category) {
                    return [$category->id => $category->full_name];
                }

                return [];
            }
        )->ajax('/admin/api/categories?is_directory=0');
        $form->image('image', __('封面图片'))->rules('required|image');
        $form->textarea('description', __('商品描述'))->rules('required');
        $form->switch('on_sale', __('上架'))->options(['1' => '是', '0' => '否'])->default('0');
        // 添加众筹相关字段
        $form->text('crowdfunding.target_amount', __('众筹目标金额'))->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at', __('众筹结束时间'))->rules('required|date');
        $form->hasMany(
            'skus',
            __('商品 SKU'),
            function (Form\NestedForm $form) {
                $form->text('title', __('SKU 名称'))->rules('required');
                $form->text('description', __('SKU 描述'))->rules('required');
                $form->text('price', __('单价'))->rules('required|numeric|min:0.01');
                $form->text('stock', __('剩余库存'))->rules('required|integer|min:0');
            }
        );
        $form->saving(
            function (Form $form) {
                $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price');
            }
        );

        return $form;
    }
}