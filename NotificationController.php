<?php

namespace jcarrasco96\notification;

use Exception;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class NotificationController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'delete-all',
                            'mark-all-read',
                            'see',
                            'delete',
                            'show-all',
                        ],
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete-all' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionDeleteAll(): Response
    {
        Notification::deleteAll([
            'user_to' => Yii::$app->user->identity->id,
            'seen' => 1,
        ]);

        return $this->asJson(['count' => $this->notificationsUnread()]);
    }

    public function actionMarkAllRead(): Response
    {
        Notification::updateAll(['seen' => 1], ['user_to' => Yii::$app->user->identity->id]);

        return $this->asJson(['count' => $this->notificationsUnread()]);
    }

    /**
     * @throws Exception
     */
    public function actionSee($id): Response
    {
        $notification = Notification::findOne(['id' => $id]);
        $notification->seen();

        return $this->asJson([
            'content' => $notification->content,
            'date' => date('m-d-Y', strtotime($notification->date)),
            'seen' => $notification->seen,
            'count' => $this->notificationsUnread(),
            'type' => $notification->getIcon(),
        ]);
    }

    /**
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function actionDelete($id): Response
    {
        $notification = Notification::findOne(['id' => $id]);
        $notification->delete();

        return $this->asJson([
            'count' => $this->notificationsUnread(),
            'message' => Yii::t('oqcheck', 'Notification deleted!'),
        ]);
    }

    /**
     * @throws Exception
     */
    public function actionShowAll(): Response
    {
        $notificaciones = Notification::find()->where(['user_to' => Yii::$app->user->identity->id])->orderBy(['date' => SORT_DESC])->all();
        $notif = [];

        $unreads = 0;

        foreach ($notificaciones as $vNotif) {
            /* @var $vNotif Notification */

            if ($vNotif->seen == 0) {
                $unreads++;
            }

            $notif[] = [
                'id' => $vNotif->id,
                'content' => $vNotif->content,
                'date' => date('m-d-Y', strtotime($vNotif->date)),
                'seen' => $vNotif->seen,
                'user_to' => $vNotif->user_to,
                'type' => $vNotif->getIcon(),
            ];
        }

        return $this->asJson([
            'notificaciones' => $notif,
            'count' => $unreads,
        ]);
    }

    private function notificationsUnread(): bool|int|string|null
    {
        return Notification::find()->where([
            'user_to' => Yii::$app->user->identity->id,
            'seen' => 0,
        ])->count();
    }

}
