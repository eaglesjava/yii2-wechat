<?php

namespace callmez\wechat\models;

use Yii;
use yii\behaviors\TimestampBehaviorl;

/**
 * 微信菜单数据表, 存储各类(后台主菜单, 模块菜单, 自定类型菜单)菜单数据
 */
class Menu extends \yii\db\ActiveRecord
{
    /**
     * 后台菜单
     */
    const TYPE_ADMIN = 'admin';
    /**
     * 模块菜单
     */
    const TYPE_MODULE = 'module';
    /**
     * 菜单数据缓存依赖TAG
     */
    const CACHE_DATA_DEPENDENCY_TAG = 'wechat_menu_data_cache';

    public function behaviors()
    {
        return [
            'timestamp' => TimestampBehavior::className(),
            'event' => [
                'class' => EventBehavior::className(),
                'events' => [
                    ActiveRecord::EVENT_BEFORE_DELETE => function($event) { // 是否能删除
                        $event->isValid = $this->getCanUninstall(true);
                    },
                    // 数据库变动必须更新缓存
                    ActiveRecord::EVENT_AFTER_INSERT => [$this, 'updateCache'],
                    ActiveRecord::EVENT_AFTER_DELETE => [$this, 'updateCache'],
                    ActiveRecord::EVENT_AFTER_UPDATE => [$this, 'updateCache'],
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_menu}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent', 'created_at', 'updated_at'], 'integer'],
            [['mid', 'title', 'type'], 'string', 'max' => 20],
            [['route'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mid' => '所属模块',
            'parent' => '父菜单',
            'title' => '菜单名',
            'route' => '访问路由',
            'type' => '菜单类型',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }

    /**
     * 更新列表数据缓存
     * @param $cacheKey
     */
    public function updateCache($cacheKey = self::CACHE_DATA_DEPENDENCY_TAG)
    {
        $cache = Yii::$app->get(static::getDb()->queryCache, false);
        if ($cache instanceof Cache) {
            TagDependency::invalidate($cache, $cacheKey);
        }
    }
}
