<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\ChatbotFile;
use app\helpers\ChatbotFileExtractor;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use app\models\User;

class ChatbotFileController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Yii::$app->user->identity->role === User::ROLE_ADMIN;
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $files = ChatbotFile::find()->orderBy(['created_at' => SORT_DESC])->all();
        return $this->render('index', ['files' => $files]);
    }

    public function actionUpload()
    {
        if (!Yii::$app->request->isPost) {
            return $this->redirect(['index']);
        }
        $file = UploadedFile::getInstanceByName('chatbot_file');
        if (!$file || $file->error !== UPLOAD_ERR_OK) {
            Yii::$app->session->setFlash('error', 'Выберите файл для загрузки.');
            return $this->redirect(['index']);
        }
        $allowed = ['txt', 'docx', 'xlsx'];
        $ext = strtolower($file->extension);
        if (!in_array($ext, $allowed, true)) {
            Yii::$app->session->setFlash('error', 'Разрешены только: Word (docx), блокнот (txt), Excel (xlsx).');
            return $this->redirect(['index']);
        }
        $dir = Yii::getAlias('@runtime/chatbot_uploads');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = substr(md5(uniqid((string)mt_rand(), true)), 0, 12) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->name);
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!$file->saveAs($path)) {
            Yii::$app->session->setFlash('error', 'Ошибка сохранения файла.');
            return $this->redirect(['index']);
        }
        $content = ChatbotFileExtractor::extract($path, $ext);
        $model = new ChatbotFile();
        $model->original_name = $file->name;
        $model->stored_path = $path;
        $model->content_text = $content;
        $model->is_active = true;
        if (!$model->save(false)) {
            @unlink($path);
            Yii::$app->session->setFlash('error', 'Ошибка сохранения в базу.');
            return $this->redirect(['index']);
        }
        Yii::$app->session->setFlash('success', 'Файл «' . htmlspecialchars($file->name) . '» загружен.');
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        try {
            $model = ChatbotFile::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        } catch (\Exception $e) {
            $model = null;
        }
        if (!$model) {
            throw new \yii\web\NotFoundHttpException();
        }
        if (is_file($model->stored_path)) {
            @unlink($model->stored_path);
        }
        $model->delete();
        Yii::$app->session->setFlash('success', 'Файл удалён.');
        return $this->redirect(['index']);
    }

    public function actionToggleActive($id)
    {
        try {
            $model = ChatbotFile::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        } catch (\Exception $e) {
            $model = null;
        }
        if (!$model) {
            throw new \yii\web\NotFoundHttpException();
        }
        $model->is_active = !$model->getIsActiveForContext();
        $model->save(false);
        Yii::$app->session->setFlash('success', $model->is_active ? 'Файл снова используется ботом.' : 'Файл деактивирован — бот его не читает.');
        return $this->redirect(['index']);
    }

    public function actionView($id)
    {
        try {
            $model = ChatbotFile::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        } catch (\Exception $e) {
            $model = null;
        }
        if (!$model) {
            throw new \yii\web\NotFoundHttpException();
        }
        return $this->render('view', ['model' => $model]);
    }

    public function actionDownload($id)
    {
        try {
            $model = ChatbotFile::findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        } catch (\Exception $e) {
            $model = null;
        }
        if (!$model || !is_file($model->stored_path)) {
            throw new \yii\web\NotFoundHttpException();
        }
        return Yii::$app->response->sendFile($model->stored_path, $model->original_name, [
            'inline' => false,
        ]);
    }
}
