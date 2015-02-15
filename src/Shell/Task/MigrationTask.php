<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Migrations\Shell\Task;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Migrations\Util\ColumnParser;
use Migrations\Shell\Task\SimpleMigrationTask;

/**
 * Task class for generating migration snapshot files.
 */
class MigrationTask extends SimpleMigrationTask
{
    /**
     * {@inheritDoc}
     */
    public function template()
    {
        return 'Migrations.config/skeleton';
    }

    /**
     * {@inheritdoc}
     */
    public function templateData()
    {
        $className = $this->BakeTemplate->viewVars['name'];
        $namespace = Configure::read('App.namespace');
        $pluginPath = '';
        if ($this->plugin) {
            $namespace = $this->plugin;
            $pluginPath = $this->plugin . '.';
        }

        $collection = $this->getCollection($this->connection);
        $action = $this->detectAction($className);

        if ($action === null) {
            return [
                'plugin' => $this->plugin,
                'pluginPath' => $pluginPath,
                'namespace' => $namespace,
                'collection' => $collection,
                'tables' => [],
                'action' => null,
                'name' => $className
            ];
        }

        $arguments = $this->args;
        unset($arguments[0]);
        $columnParser = new ColumnParser;
        $fields = $columnParser->parseFields($arguments);
        $indexes = $columnParser->parseIndexes($arguments);

        list($action, $table) = $action;
        return [
            'plugin' => $this->plugin,
            'pluginPath' => $pluginPath,
            'namespace' => $namespace,
            'collection' => $collection,
            'tables' => [$table],
            'action' => $action,
            'columns' => [
                'fields' => $fields,
                'indexes' => $indexes,
            ],
            'name' => $className
        ];
    }

    public function detectAction($name)
    {
        if (preg_match('/^(Create|Drop)(.*)/', $name, $matches)) {
            $action = strtolower($matches[1]) . '_table';
            $table = Inflector::tableize(Inflector::pluralize($matches[2]));
        } elseif (preg_match('/^(Add).*(?:To)(.*)/', $name, $matches)) {
            $action = 'add_field';
            $table = Inflector::tableize(Inflector::pluralize($matches[2]));
        } elseif (preg_match('/^(Remove).*(?:From)(.*)/', $name, $matches)) {
            $action = 'drop_field';
            $table = Inflector::tableize(Inflector::pluralize($matches[2]));
        } elseif (preg_match('/^(Alter)(.*)/', $name, $matches)) {
            $action = 'alter_table';
            $table = Inflector::tableize(Inflector::pluralize($matches[2]));
        } else {
            return null;
        }

        return [$action, $table];
    }
}
