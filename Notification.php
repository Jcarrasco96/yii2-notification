<?php

namespace jcarrasco96\notification;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use app\models\User;
use Yii;
use yii\helpers\Url;
use yii\web\View;

/**
 * This is the model class for table "notification".
 *
 * @property int $id
 * @property string $content
 * @property string $date
 * @property int $seen
 * @property int $type
 * @property int $user_to
 *
 * @property User $userTo
 */
class Notification extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'notification';
    }

    public function rules(): array
    {
        return [
            [['content', 'seen', 'user_to'], 'required'],
            [['date'], 'safe'],
            [['seen', 'user_to', 'type'], 'integer'],
            [['content'], 'string', 'max' => 255],
            [['user_to'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_to' => 'id']],
        ];
    }

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    parent::EVENT_BEFORE_INSERT => ['date'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'content' => 'Content',
            'date' => 'Date',
            'seen' => 'Seen',
            'type' => 'Type',
            'user_to' => 'User to',
        ];
    }

    public function getUserTo(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_to']);
    }

    public function getDateFormatted(): string
    {
        return date_create($this->date)->format('l jS F Y');
    }

    public function seen(): void
    {
        $this->seen = $this->seen == 1 ? 0 : 1;
        $this->save();
    }

    public function getIcon(): array
    {
        return match ($this->type) {
            Constants::NOTIFICATION_TYPE_SUCCESS => ['icon' => 'bi-check-circle', 'color' => 'text-success'],
            Constants::NOTIFICATION_TYPE_INFO => ['icon' => 'bi-info-circle', 'color' => 'text-info'],
            Constants::NOTIFICATION_TYPE_WARNING => ['icon' => 'bi-exclamation-triangle', 'color' => 'text-warning'],
            Constants::NOTIFICATION_TYPE_DANGER => ['icon' => 'bi-exclamation-circle', 'color' => 'text-danger'],
        };
    }

    public static function widget(View $view): string
    {
        $view->registerJsVar('urlSeeNotification', Url::to(['/notification/see']));
        $view->registerJsVar('urlDelNotification', Url::to(['/notification/delete']));
        $view->registerJsVar('urlMarkAllRedNotification', Url::to(['/notification/mark-all-read']));
        $view->registerJsVar('urlDelAllNotification', Url::to(['/notification/delete-all']));
        $view->registerJsVar('urlUnreadNotifications', Url::to(['/notification/unread']));

        $count = self::find()->where([
            'user_to' => Yii::$app->user->identity->id,
            'seen'    => 0,
        ])->count();
        $style = $count == 0 ? 'display: none' : '';

        return '<li class="dropdown nav-item" id="testdropd" data-url="' . Url::to(['/notification/show-all']) . '"><a href="#" class="nav-link" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="bi bi-bell-fill"></i><span class="notification-unread" style="' . $style . '">' . $count . '</span></a><ul id="dropNotifications" class="dropdown-menu dropdown-menu-end" style="width: 420px;"></ul></li>';
    }

    public static function create(string $content, int $user_to, int $type = Constants::NOTIFICATION_TYPE_SUCCESS): void
    {
        /* @var $user User */
        $user = User::find()->where(['id' => $user_to, 'notify_app' => 1])->one();

        if (empty($user) || empty($content)) {
            return;
        }

        $model = new Notification();
        $model->content = $content;
        $model->user_to = $user_to;
        $model->type = $type;
        $model->seen = 0;
        $model->save();
    }
    
}