<?php
/**
 * CakePHP DatabaseLog Plugin
 *
 * Licensed under The MIT License.
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/dereuromark/CakePHP-DatabaseLog
 */
namespace DatabaseLog\Controller\Admin;

use App\Controller\AppController;
use DatabaseLog\Model\Table\DatabaseLogsTable;

/**
 * @property \DatabaseLog\Model\Table\DatabaseLogsTable $DatabaseLogs
 */
class LogsController extends AppController {

	/**
	 * Explicitly use the Log model.
	 *
	 * Fixes problems with the controller test.
	 *
	 * @var string
	 */
	public $modelClass = 'DatabaseLog.DatabaseLogs';

	/**
	 * Load the TimeHelper
	 *
	 * @var array
	 */
	public $helpers = ['Time', 'DatabaseLog.Log'];

	/**
	 * Setup pagination
	 *
	 * @var array
	 */
	public $paginate = [
		'order' => ['created' => 'DESC'],
		'fields' => [
			'created',
			'type',
			'uri',
			'message',
			'id',
		],
	];

	/**
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		if (!DatabaseLogsTable::isSearchEnabled()) {
			return;
		}
		$this->loadComponent('Search.Prg', [
			'actions' => ['index'],
		]);
	}

	/**
	 * Index/Overview action
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$currentType = $this->request->getQuery('type');

		if (DatabaseLogsTable::isSearchEnabled()) {
			$query = $this->DatabaseLogs->find('search', ['search' => $this->request->getQuery()]);
		} else {
			$conditions = $this->DatabaseLogs->textSearch();
			if ($currentType) {
				$conditions['type'] = $currentType;
			}
			$query = $this->DatabaseLogs->find()->where($conditions);
		}
		$query = $query->select(['id', 'created', 'type', 'summary', 'count']);

		$logs = $this->paginate($query);
		$types = $this->DatabaseLogs->getTypes();

		$this->set(compact('logs', 'types', 'currentType'));
	}

	/**
	 * @param int|null $id The log ID to view.
	 * @return \Cake\Http\Response|null
	 */
	public function view($id = null) {
		$log = $this->DatabaseLogs->get($id);
		$this->set('log', $log);
	}

	/**
	 * Delete action
	 *
	 * @param int|null $id The log ID to delete.
	 * @return \Cake\Http\Response|null
	 */
	public function delete($id = null) {
		$this->request->allowMethod('post');
		$log = $this->DatabaseLogs->get($id);

		if ($this->DatabaseLogs->delete($log)) {
			$this->Flash->success(__('Log deleted'));
			return $this->redirect(['action' => 'index']);
		}
		$this->Flash->error(__('Log was not deleted'));
		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Reset action
	 *
	 * Deletes all log entries.
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function reset() {
		$this->request->allowMethod('post');

		$type = $this->request->getQuery('type');
		if ($type) {
			$this->DatabaseLogs->deleteAll([
				'type' => $type
			]);
		} else {
			$this->DatabaseLogs->truncate();
		}

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * Remove duplicates action
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function removeDuplicates() {
		$this->request->allowMethod('post');

		$this->DatabaseLogs->removeDuplicates((bool)$this->request->getQuery('strict'));

		$this->Flash->success(__('Duplicates have been removed.'));
		return $this->redirect(['action' => 'index']);
	}

}
