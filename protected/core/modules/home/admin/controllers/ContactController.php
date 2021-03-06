<?php

namespace core\modules\home\admin\controllers;

use moonland\phpexcel\Excel;
use Yii;
use core\modules\home\models\Contact;
use core\modules\home\models\search\CantactSearch;
use core\modules\admin\components\Controller;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ContactController implements the CRUD actions for Contact model.
 */
class ContactController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return ArrayHelper::merge($behaviors, [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Lists all Contact models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CantactSearch();
        $params = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($params);

        if (isset($params['export']) && $params['export'] == 1) {
            $this->export($dataProvider->getModels());
            exit;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Contact model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if ($model->status == $model::STATUS_UNREAD) {
            $model->status = $model::STATUS_READ;
            $model->save();
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Contact model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Contact();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Contact model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Contact model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        //Delete current Banner.
        $model->status = $model::STATUS_DELETED;
        $model->save(false);

        return $this->redirect(['index']);
    }

    /**
     * Batch delete the contact message.
     * @return \yii\web\Response
     */
    public function actionBatchDelete()
    {
        $ids = Yii::$app->request->post('ids');
        if (is_array($ids)) {
            Contact::updateAll(['status' => Contact::STATUS_DELETED], [
                'and',
                ['<>', 'status', Contact::STATUS_DELETED],
                ['in', 'id', $ids]
            ]);
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('HomeModule.base', 'Delete successfully')
            );
        }
        return $this->redirect(['index']);
    }

    /**
     * Batch read the contact message.
     * @return \yii\web\Response
     */
    public function actionBatchRead()
    {
        $ids = Yii::$app->request->post('ids');
        if (is_array($ids)) {
            Contact::updateAll(['status' => Contact::STATUS_READ], [
                'and',
                ['status' => Contact::STATUS_UNREAD],
                ['in', 'id', $ids]
            ]);
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('HomeModule.base', 'Read successfully')
            );
        }
        return $this->redirect(['index']);
    }

    /**
     * Finds the Contact model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Contact the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Contact::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Export contact message
     */
    public function export($models)
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . Yii::t('HomeModule.base', 'Message List') . '.xlsx');
        Excel::export([
            'models' => $models,
            'columns' => [
                'name',
                'company',
                'mobile',
                'email',
                'demand:ntext',
                'created_at:datetime',
                [
                    'attribute' => 'status',
                    'value' => function ($model) {
                        return Contact::getStatus($model->status);
                    },
                    'format' => 'raw',
                ],
            ],
        ]);
    }
}
