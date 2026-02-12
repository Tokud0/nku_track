<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * ProjectDocument model (stores official project documents: PDF/DOC/DOCX).
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $project_id
 * @property \MongoDB\BSON\ObjectId $uploaded_by_user_id
 * @property string $file_name
 * @property string $file_type
 * @property int $file_size
 * @property \MongoDB\BSON\Binary $file_data
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class ProjectDocument extends ActiveRecord
{
    public static function collectionName()
    {
        return 'project_documents';
    }

    public function attributes()
    {
        return [
            '_id',
            'project_id',
            'uploaded_by_user_id',
            'file_name',
            'file_type',
            'file_size',
            'file_data',
            'created_at',
            'updated_at',
        ];
    }

    public function rules()
    {
        return [
            [['project_id', 'uploaded_by_user_id', 'file_name', 'file_type', 'file_size', 'file_data'], 'required'],
            [['file_name', 'file_type'], 'string'],
            [['file_size'], 'integer', 'min' => 1],
            [['project_id', 'uploaded_by_user_id', 'file_data', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            $this->created_at = new \MongoDB\BSON\UTCDateTime();
        }
        $this->updated_at = new \MongoDB\BSON\UTCDateTime();

        return true;
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['_id' => 'project_id']);
    }
}

