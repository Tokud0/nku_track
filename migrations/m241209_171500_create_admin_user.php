<?php

use yii\mongodb\Migration;
use app\models\User;

/**
 * Class m241209_171500_create_admin_user
 * Миграция для создания администратора системы
 */
class m241209_171500_create_admin_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // Проверяем, не существует ли уже администратор
        $adminExists = User::find()
            ->where(['email' => 'admin@example.com'])
            ->orWhere(['role' => User::ROLE_ADMIN])
            ->exists();

        if ($adminExists) {
            echo "Администратор уже существует. Миграция пропущена.\n";
            return true;
        }

        // Создаем администратора
        $admin = new User();
        $admin->fio = 'Администратор системы';
        $admin->email = 'admin@example.com';
        $admin->role = User::ROLE_ADMIN;
        $admin->department = 'Администрация';
        $admin->setPassword('admin123'); // Пароль по умолчанию - измените его после первого входа!
        
        if ($admin->save()) {
            echo "Администратор успешно создан!\n";
            echo "Email: admin@example.com\n";
            echo "Пароль: admin123\n";
            echo "ВАЖНО: Измените пароль после первого входа!\n";
            return true;
        } else {
            echo "Ошибка при создании администратора:\n";
            foreach ($admin->getErrors() as $attribute => $errors) {
                foreach ($errors as $error) {
                    echo "  - $attribute: $error\n";
                }
            }
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        echo "m241209_171500_create_admin_user cannot be reverted.\n";
        echo "Если нужно удалить администратора, сделайте это вручную.\n";
        return false;
    }
}

