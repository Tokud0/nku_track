<?php

namespace app\controllers;

use Yii;
use app\models\TaskExecutorRequest;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Заявки на прикрепление сотрудника подразделения к задаче другого подразделения.
 * Руководитель или топ-менеджер одобряет/отклоняет заявки по своему подразделению.
 */
class TaskExecutorRequestController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'approve' => ['POST'],
                    'reject' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Список ожидающих заявок по подразделению текущего пользователя.
     * Доступно только руководителю и топ-менеджеру своего подразделения.
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        if (!in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER], true)) {
            throw new ForbiddenHttpException('Доступ только для руководителя или топ-менеджера подразделения.');
        }
        if (!$user->department_id) {
            throw new ForbiddenHttpException('Вы не привязаны к подразделению.');
        }

        $requests = TaskExecutorRequest::getPendingByDepartment($user->department_id);

        return $this->render('index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Одобрить заявку: добавить исполнителя в задачу.
     */
    public function actionApprove($id)
    {
        $request = $this->findRequest($id);
        $user = Yii::$app->user->identity;
        $this->assertCanDecide($request, $user);

        $task = $request->task;
        if (!$task) {
            throw new NotFoundHttpException('Задача не найдена.');
        }
        $executorUserIds = $task->executor_user_ids;
        if (!is_array($executorUserIds)) {
            $executorUserIds = [];
        }
        $userIdStr = (string)$request->user_id;
        $exists = false;
        foreach ($executorUserIds as $uid) {
            if ((string)$uid === $userIdStr) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $executorUserIds[] = $request->user_id;
            $task->executor_user_ids = $executorUserIds;
            $task->save(false);
        }
        $request->status = TaskExecutorRequest::STATUS_APPROVED;
        $request->decided_by = $user->_id;
        $request->decided_at = new \MongoDB\BSON\UTCDateTime();
        $request->save(false);

        Yii::$app->session->setFlash('success', 'Заявка одобрена. Исполнитель прикреплён к задаче.');
        return $this->redirect(['index']);
    }

    /**
     * Отклонить заявку.
     */
    public function actionReject($id)
    {
        $request = $this->findRequest($id);
        $user = Yii::$app->user->identity;
        $this->assertCanDecide($request, $user);

        $request->status = TaskExecutorRequest::STATUS_REJECTED;
        $request->decided_by = $user->_id;
        $request->decided_at = new \MongoDB\BSON\UTCDateTime();
        $request->save(false);

        Yii::$app->session->setFlash('info', 'Заявка отклонена.');
        return $this->redirect(['index']);
    }

    /**
     * @param string $id
     * @return TaskExecutorRequest
     * @throws NotFoundHttpException
     */
    protected function findRequest($id)
    {
        $idObj = is_string($id) ? new \MongoDB\BSON\ObjectId($id) : $id;
        $model = TaskExecutorRequest::findOne(['_id' => $idObj]);
        if ($model === null) {
            throw new NotFoundHttpException('Заявка не найдена.');
        }
        return $model;
    }

    /**
     * Проверка: текущий пользователь — руководитель/топ-менеджер подразделения исполнителя по заявке.
     */
    protected function assertCanDecide(TaskExecutorRequest $request, User $user)
    {
        if (!in_array($user->role, [User::ROLE_HEAD, User::ROLE_TOP_MANAGER], true)) {
            throw new ForbiddenHttpException('Только руководитель или топ-менеджер подразделения может решать по заявке.');
        }
        if ($request->status !== TaskExecutorRequest::STATUS_PENDING) {
            throw new ForbiddenHttpException('Заявка уже рассмотрена.');
        }
        $executor = $request->user;
        if (!$executor || !$executor->department_id) {
            throw new ForbiddenHttpException('Исполнитель не в подразделении.');
        }
        if (!$user->department_id || (string)$user->department_id !== (string)$executor->department_id) {
            throw new ForbiddenHttpException('Вы можете решать только заявки по сотрудникам своего подразделения.');
        }
    }
}
