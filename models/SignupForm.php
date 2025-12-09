<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * SignupForm is the model behind the signup form.
 */
class SignupForm extends Model
{
    public $fio;
    public $email;
    public $password;
    public $password_repeat;
    public $department_id;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['fio', 'email', 'password', 'password_repeat'], 'required'],
            ['email', 'email'],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'Этот email уже используется.'],
            [['fio'], 'string', 'max' => 255],
            [['department_id'], 'exist', 'targetClass' => \app\models\Department::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            ['password', 'string', 'min' => 6, 'message' => 'Пароль должен содержать минимум 6 символов.'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают.'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'fio' => 'ФИО',
            'email' => 'Email',
            'password' => 'Пароль',
            'password_repeat' => 'Повторите пароль',
            'department_id' => 'Подразделение',
        ];
    }

    /**
     * Signs user up.
     *
     * @return bool whether the user was created successfully
     */
    public function signup()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = new User();
        $user->fio = $this->fio;
        $user->email = $this->email;
        $user->department_id = $this->department_id ? new \MongoDB\BSON\ObjectId($this->department_id) : null;
        $user->role = User::ROLE_EXECUTOR; // Автоматически устанавливаем роль executor
        $user->setPassword($this->password);

        if ($user->save()) {
            return true;
        } else {
            // Добавляем ошибки из модели User в форму
            foreach ($user->getErrors() as $attribute => $errors) {
                foreach ($errors as $error) {
                    $this->addError($attribute, $error);
                }
            }
        }

        return false;
    }
}

