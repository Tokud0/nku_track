<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\Department;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * Показывает состав подразделения текущего пользователя:
 * подразделение (department), его сотрудники и департаменты с составом.
 */
class DepartmentStaffController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Состав подразделения: информация о подразделении, прямые сотрудники и департаменты с составом.
     * @return string
     * @throws ForbiddenHttpException если пользователь не привязан к подразделению
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        if (!$user->department_id) {
            throw new ForbiddenHttpException('Вы не привязаны к подразделению. Доступ к составу подразделения недоступен.');
        }

        $department = Department::findOne($user->department_id);
        if (!$department) {
            throw new ForbiddenHttpException('Подразделение не найдено.');
        }

        // Сотрудники напрямую в подразделении (без департамента), сортировка по иерархии ролей
        $directStaff = User::find()
            ->where(['department_id' => $department->_id])
            ->andWhere(['subdepartment_id' => null])
            ->all();
        $this->sortUsersByRoleHierarchy($directStaff);

        // Департаменты (дочерние подразделения) с их составом
        $subdepartments = $department->getSubdepartments()->orderBy(['name' => SORT_ASC])->all();
        $subdepartmentsWithStaff = [];
        foreach ($subdepartments as $sub) {
            $users = User::find()
                ->where(['subdepartment_id' => $sub->_id])
                ->all();
            $this->sortUsersByRoleHierarchy($users);
            $subdepartmentsWithStaff[] = [
                'department' => $sub,
                'users' => $users,
            ];
        }

        return $this->render('index', [
            'department' => $department,
            'directStaff' => $directStaff,
            'subdepartmentsWithStaff' => $subdepartmentsWithStaff,
        ]);
    }

    /**
     * Сортировка пользователей по иерархии ролей: руководитель → топ-менеджер → менеджер → исполнитель
     * (админ и ректор — в начале списка). Внутри одной роли — по ФИО.
     * @param User[] $users массив изменяется по месту
     */
    private function sortUsersByRoleHierarchy(array &$users): void
    {
        $roleOrder = [
            User::ROLE_ADMIN => 0,
            User::ROLE_RECTOR => 1,
            User::ROLE_HEAD => 2,
            User::ROLE_TOP_MANAGER => 3,
            User::ROLE_MANAGER => 4,
            User::ROLE_EXECUTOR => 5,
        ];
        usort($users, function (User $a, User $b) use ($roleOrder) {
            $orderA = $roleOrder[$a->role] ?? 99;
            $orderB = $roleOrder[$b->role] ?? 99;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            return strcasecmp($a->fio ?? '', $b->fio ?? '');
        });
    }
}
