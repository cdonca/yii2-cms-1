<?php

namespace core\modules\news\controllers;

use core\models\Category;
use core\modules\news\models\News;
use yii\data\Pagination;
use yii\web\Controller;

class IndexController extends Controller
{

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $cate = Category::find()
            ->where(['type' => Category::TYPE_NEWS, 'status' => Category::STATUS_ENABLED])
            ->orderBy('sort_order asc')
            ->limit(10)
            ->all();

        $current_cat = "全 部";

        $data = News::find()
            ->where(['status' => News::STATUS_ENABLED])
            ->orderBy('sort_order asc');
        if (isset($_GET['id'])) {
            $data->andWhere(['category_id' => $_GET['id']]);
            foreach ($cate as $c) {
                if ($c->id == $_GET['id']) {
                    $current_cat = $c->name;
                    break;
                }
            }
        }
        $pages = new Pagination(['totalCount' => $data->count(), 'pageSize' => '12']);
        $model = $data->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('index',
            ['cate' => $cate, 'pages' => $pages, 'model' => $model, 'current_cat' => $current_cat]);
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionDetail($id)
    {
        $model = News::findOne($id);
        return $this->render('detail', [
            'model' => $model,
        ]);
    }

}
