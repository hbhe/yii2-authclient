<?php
use yii\db\Migration;
use yii\helpers\Console;
use yii\helpers\FileHelper;

class m161007_021200_init extends Migration
{
    public function up()
    {
        $faker = \Faker\Factory::create('zh_CN');

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=MyISAM';
        }

        Yii::$app->db->createCommand("DROP TABLE IF EXISTS {{%member}}")->execute();
        $this->createTable('{{%member}}', [
            'id' => $this->primaryKey(),
            'sid' => $this->string(64)->unique(),
            'mobile' => $this->string(16)->comment('手机')->unique(),
            'name' => $this->string(32)->comment('姓名'),
            'auth_key' => $this->string(32)->notNull(),
            'access_token' => $this->string(40)->notNull(),
            'password_plain' => $this->string()->comment('密码明码'),
            'password_hash' => $this->string()->notNull()->defaultValue(''),
            'email' => $this->string(),
            'status' => $this->smallInteger()->notNull()->defaultValue(Member::STATUS_ACTIVE), // 1:活动，2失效,
            'avatar_path' => $this->string(),
            'avatar_base_url' => $this->string()->comment('头像'),
            'gender' => $this->string(4)->comment('性别'), // f, m
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('创建时间'),
            'updated_at' => $this->timestamp()->defaultValue(null)->comment('更新时间'),
            'logged_at' => $this->timestamp()->defaultValue(null)->comment('最近登录'),
        ], $tableOptions);
        $this->addCommentOnTable('{{%member}}', '用户');

        Yii::$app->db->createCommand("DROP TABLE IF EXISTS {{%auth}}")->execute();
        $this->createTable('{{%auth}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'oauth_client' => $this->string(64)->comment('第三方名称'), // 'qq', 'weixin', 'github'
            'oauth_client_user_id' => $this->string(), // qq ID, weixin ID, ...
            'nickname' => $this->string(),
            'avatar_url' => $this->string(),
        ], $tableOptions);
        $this->addCommentOnTable('{{%auth}}', '第三方登录账号');
        $this->addForeignKey('fk_user', '{{%auth}}', 'user_id', '{{%member}}', 'id', 'cascade', 'cascade');
        $this->createIndex('oauth_client', '{{%auth}}', ['oauth_client', 'oauth_client_user_id(128)'], true);

        return true;
    }

    public function down()
    {
        $this->dropTable('{{%auth}}');
        $this->dropTable('{{%member}}');
        return true;
    }
}

