<?php

use yii\db\Migration;

/**
 * Class m260116_130404_create_author_subscriptions_table
 */
class m260116_130404_create_author_subscriptions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%author_subscriptions}}', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull(),
            'email' => $this->string(255)->null(),
            'phone' => $this->string(20)->null(),
            'created_at' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey(
            'fk-author_subscriptions-author_id',
            '{{%author_subscriptions}}',
            'author_id',
            '{{%authors}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('idx-author_subscriptions-author_id', '{{%author_subscriptions}}', 'author_id');
        $this->createIndex('idx-author_subscriptions-email', '{{%author_subscriptions}}', 'email');
        $this->createIndex('idx-author_subscriptions-phone', '{{%author_subscriptions}}', 'phone');
        $this->addCheck(
            'chk-author_subscriptions-contact-required',
            '{{%author_subscriptions}}',
            "((`email` IS NOT NULL AND TRIM(`email`) <> '') OR (`phone` IS NOT NULL AND TRIM(`phone`) <> ''))"
        );
        $this->createIndex('ux-author_subscriptions-author-email', '{{%author_subscriptions}}', ['author_id', 'email'], true);
        $this->createIndex('ux-author_subscriptions-author-phone', '{{%author_subscriptions}}', ['author_id', 'phone'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%author_subscriptions}}');
    }
}
