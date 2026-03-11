<?php

namespace Drupal\rareimagery_ai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Registry of tools that AI agents can invoke on the Drupal site.
 */
class ToolRegistry {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected ConfigFactoryInterface $configFactory;
  protected ModuleHandlerInterface $moduleHandler;
  protected Connection $database;
  protected $logger;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->database = $database;
    $this->logger = $logger_factory->get('rareimagery_ai');
  }

  /**
   * Returns tool definitions in a format compatible with Claude and xAI tool-use.
   */
  public function getToolDefinitions(): array {
    return [
      [
        'name' => 'list_content',
        'description' => 'List content (nodes) on the site. Optionally filter by type and status.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'content_type' => ['type' => 'string', 'description' => 'Machine name of content type (e.g. x_creator_store, article). Leave empty for all.'],
            'status' => ['type' => 'string', 'enum' => ['published', 'unpublished', 'all'], 'description' => 'Filter by publish status.'],
            'limit' => ['type' => 'integer', 'description' => 'Max results to return. Default 25.'],
          ],
          'required' => [],
        ],
      ],
      [
        'name' => 'get_content',
        'description' => 'Get full details of a specific node by ID.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'nid' => ['type' => 'integer', 'description' => 'The node ID.'],
          ],
          'required' => ['nid'],
        ],
      ],
      [
        'name' => 'create_content',
        'description' => 'Create a new node (content) on the site.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'type' => ['type' => 'string', 'description' => 'Content type machine name.'],
            'title' => ['type' => 'string', 'description' => 'Node title.'],
            'body' => ['type' => 'string', 'description' => 'Body text (HTML allowed).'],
            'status' => ['type' => 'boolean', 'description' => 'Published (true) or unpublished (false).'],
            'fields' => ['type' => 'object', 'description' => 'Additional field values as key-value pairs.'],
          ],
          'required' => ['type', 'title'],
        ],
      ],
      [
        'name' => 'update_content',
        'description' => 'Update an existing node by ID.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'nid' => ['type' => 'integer', 'description' => 'The node ID to update.'],
            'title' => ['type' => 'string', 'description' => 'New title.'],
            'body' => ['type' => 'string', 'description' => 'New body text.'],
            'status' => ['type' => 'boolean', 'description' => 'Published status.'],
            'fields' => ['type' => 'object', 'description' => 'Field values to update.'],
          ],
          'required' => ['nid'],
        ],
      ],
      [
        'name' => 'delete_content',
        'description' => 'Delete a node by ID. This is permanent.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'nid' => ['type' => 'integer', 'description' => 'The node ID to delete.'],
          ],
          'required' => ['nid'],
        ],
      ],
      [
        'name' => 'list_users',
        'description' => 'List user accounts on the site.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'role' => ['type' => 'string', 'description' => 'Filter by role machine name.'],
            'status' => ['type' => 'string', 'enum' => ['active', 'blocked', 'all'], 'description' => 'Filter by account status.'],
            'limit' => ['type' => 'integer', 'description' => 'Max results. Default 25.'],
          ],
          'required' => [],
        ],
      ],
      [
        'name' => 'manage_user',
        'description' => 'Block, unblock, add role, or remove role from a user.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'uid' => ['type' => 'integer', 'description' => 'The user ID.'],
            'action' => ['type' => 'string', 'enum' => ['block', 'unblock', 'add_role', 'remove_role'], 'description' => 'Action to perform.'],
            'role' => ['type' => 'string', 'description' => 'Role machine name (required for add_role/remove_role).'],
          ],
          'required' => ['uid', 'action'],
        ],
      ],
      [
        'name' => 'list_commerce_products',
        'description' => 'List commerce products. Optionally filter by type or store.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'product_type' => ['type' => 'string', 'description' => 'Product type: physical_pod, physical_custom, digital_download.'],
            'store_id' => ['type' => 'integer', 'description' => 'Filter by store ID.'],
            'limit' => ['type' => 'integer', 'description' => 'Max results. Default 25.'],
          ],
          'required' => [],
        ],
      ],
      [
        'name' => 'list_commerce_orders',
        'description' => 'List commerce orders. Optionally filter by state or type.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'state' => ['type' => 'string', 'description' => 'Order state: draft, completed, canceled.'],
            'order_type' => ['type' => 'string', 'description' => 'Order type: pod_order, custom_order, digital_order.'],
            'limit' => ['type' => 'integer', 'description' => 'Max results. Default 25.'],
          ],
          'required' => [],
        ],
      ],
      [
        'name' => 'get_config',
        'description' => 'Read a Drupal configuration object.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'name' => ['type' => 'string', 'description' => 'Config name (e.g. system.site, rareimagery_xstore.settings).'],
          ],
          'required' => ['name'],
        ],
      ],
      [
        'name' => 'set_config',
        'description' => 'Update a value in a Drupal configuration object.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'name' => ['type' => 'string', 'description' => 'Config name.'],
            'key' => ['type' => 'string', 'description' => 'Config key to set.'],
            'value' => ['description' => 'Value to set (string, number, boolean, or array).'],
          ],
          'required' => ['name', 'key', 'value'],
        ],
      ],
      [
        'name' => 'clear_cache',
        'description' => 'Clear all Drupal caches.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [],
          'required' => [],
        ],
      ],
      [
        'name' => 'site_status',
        'description' => 'Get site status: Drupal version, PHP version, database info, module count, content counts.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [],
          'required' => [],
        ],
      ],
      [
        'name' => 'recent_logs',
        'description' => 'Get recent watchdog log entries.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'severity' => ['type' => 'string', 'enum' => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'], 'description' => 'Filter by severity level.'],
            'type' => ['type' => 'string', 'description' => 'Filter by log type (e.g. php, system, cron).'],
            'limit' => ['type' => 'integer', 'description' => 'Max results. Default 20.'],
          ],
          'required' => [],
        ],
      ],
      [
        'name' => 'sql_query',
        'description' => 'Run a read-only SQL SELECT query. Only SELECT statements are allowed.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'query' => ['type' => 'string', 'description' => 'The SQL SELECT query to execute.'],
          ],
          'required' => ['query'],
        ],
      ],
      [
        'name' => 'manage_module',
        'description' => 'Enable or disable a Drupal module.',
        'input_schema' => [
          'type' => 'object',
          'properties' => [
            'module' => ['type' => 'string', 'description' => 'Module machine name.'],
            'action' => ['type' => 'string', 'enum' => ['enable', 'disable'], 'description' => 'Enable or disable.'],
          ],
          'required' => ['module', 'action'],
        ],
      ],
    ];
  }

  /**
   * Execute a tool by name with the given input parameters.
   */
  public function execute(string $tool_name, array $input): array {
    $this->logger->info('AI tool call: @tool with @input', [
      '@tool' => $tool_name,
      '@input' => json_encode($input),
    ]);

    try {
      return match ($tool_name) {
        'list_content' => $this->listContent($input),
        'get_content' => $this->getContent($input),
        'create_content' => $this->createContent($input),
        'update_content' => $this->updateContent($input),
        'delete_content' => $this->deleteContent($input),
        'list_users' => $this->listUsers($input),
        'manage_user' => $this->manageUser($input),
        'list_commerce_products' => $this->listCommerceProducts($input),
        'list_commerce_orders' => $this->listCommerceOrders($input),
        'get_config' => $this->getConfig($input),
        'set_config' => $this->setConfig($input),
        'clear_cache' => $this->clearCache(),
        'site_status' => $this->siteStatus(),
        'recent_logs' => $this->recentLogs($input),
        'sql_query' => $this->sqlQuery($input),
        'manage_module' => $this->manageModule($input),
        default => ['error' => "Unknown tool: $tool_name"],
      };
    }
    catch (\Exception $e) {
      $this->logger->error('AI tool error: @tool - @msg', [
        '@tool' => $tool_name,
        '@msg' => $e->getMessage(),
      ]);
      return ['error' => $e->getMessage()];
    }
  }

  protected function listContent(array $input): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()->accessCheck(FALSE);

    if (!empty($input['content_type'])) {
      $query->condition('type', $input['content_type']);
    }
    if (isset($input['status']) && $input['status'] !== 'all') {
      $query->condition('status', $input['status'] === 'published' ? 1 : 0);
    }

    $limit = $input['limit'] ?? 25;
    $ids = $query->sort('changed', 'DESC')->range(0, $limit)->execute();
    $nodes = $storage->loadMultiple($ids);

    $results = [];
    foreach ($nodes as $node) {
      $results[] = [
        'nid' => $node->id(),
        'type' => $node->bundle(),
        'title' => $node->getTitle(),
        'status' => $node->isPublished() ? 'published' : 'unpublished',
        'changed' => date('Y-m-d H:i:s', $node->getChangedTime()),
        'author' => $node->getOwner()->getDisplayName(),
      ];
    }
    return ['count' => count($results), 'nodes' => $results];
  }

  protected function getContent(array $input): array {
    $node = Node::load($input['nid']);
    if (!$node) {
      return ['error' => "Node {$input['nid']} not found."];
    }

    $fields = [];
    foreach ($node->getFields() as $name => $field) {
      if (!$field->isEmpty() && !str_starts_with($name, 'revision_')) {
        $fields[$name] = $field->getString();
      }
    }

    return [
      'nid' => $node->id(),
      'type' => $node->bundle(),
      'title' => $node->getTitle(),
      'status' => $node->isPublished() ? 'published' : 'unpublished',
      'created' => date('Y-m-d H:i:s', $node->getCreatedTime()),
      'changed' => date('Y-m-d H:i:s', $node->getChangedTime()),
      'author' => $node->getOwner()->getDisplayName(),
      'fields' => $fields,
    ];
  }

  protected function createContent(array $input): array {
    $values = [
      'type' => $input['type'],
      'title' => $input['title'],
      'status' => $input['status'] ?? TRUE,
    ];

    if (!empty($input['body'])) {
      $values['body'] = [
        'value' => $input['body'],
        'format' => 'full_html',
      ];
    }

    if (!empty($input['fields'])) {
      foreach ($input['fields'] as $key => $value) {
        $values[$key] = $value;
      }
    }

    $node = Node::create($values);
    $node->save();

    return [
      'success' => TRUE,
      'nid' => $node->id(),
      'message' => "Created {$input['type']} node '{$input['title']}' (nid: {$node->id()}).",
    ];
  }

  protected function updateContent(array $input): array {
    $node = Node::load($input['nid']);
    if (!$node) {
      return ['error' => "Node {$input['nid']} not found."];
    }

    if (isset($input['title'])) {
      $node->setTitle($input['title']);
    }
    if (isset($input['body'])) {
      $node->set('body', ['value' => $input['body'], 'format' => 'full_html']);
    }
    if (isset($input['status'])) {
      $node->setPublished($input['status']);
    }
    if (!empty($input['fields'])) {
      foreach ($input['fields'] as $key => $value) {
        if ($node->hasField($key)) {
          $node->set($key, $value);
        }
      }
    }

    $node->save();
    return ['success' => TRUE, 'message' => "Updated node {$input['nid']} '{$node->getTitle()}'."];
  }

  protected function deleteContent(array $input): array {
    $node = Node::load($input['nid']);
    if (!$node) {
      return ['error' => "Node {$input['nid']} not found."];
    }
    $title = $node->getTitle();
    $node->delete();
    return ['success' => TRUE, 'message' => "Deleted node {$input['nid']} '$title'."];
  }

  protected function listUsers(array $input): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery()->accessCheck(FALSE);
    $query->condition('uid', 0, '>');

    if (!empty($input['role'])) {
      $query->condition('roles', $input['role']);
    }
    if (isset($input['status']) && $input['status'] !== 'all') {
      $query->condition('status', $input['status'] === 'active' ? 1 : 0);
    }

    $limit = $input['limit'] ?? 25;
    $ids = $query->sort('created', 'DESC')->range(0, $limit)->execute();
    $users = $storage->loadMultiple($ids);

    $results = [];
    foreach ($users as $user) {
      $results[] = [
        'uid' => $user->id(),
        'name' => $user->getDisplayName(),
        'mail' => $user->getEmail(),
        'status' => $user->isActive() ? 'active' : 'blocked',
        'roles' => $user->getRoles(TRUE),
        'last_login' => $user->getLastLoginTime() ? date('Y-m-d H:i:s', $user->getLastLoginTime()) : 'never',
      ];
    }
    return ['count' => count($results), 'users' => $results];
  }

  protected function manageUser(array $input): array {
    $user = User::load($input['uid']);
    if (!$user) {
      return ['error' => "User {$input['uid']} not found."];
    }
    if ((int) $input['uid'] === 1) {
      return ['error' => 'Cannot modify the super admin account (uid 1).'];
    }

    switch ($input['action']) {
      case 'block':
        $user->block();
        $user->save();
        return ['success' => TRUE, 'message' => "Blocked user {$user->getDisplayName()}."];

      case 'unblock':
        $user->activate();
        $user->save();
        return ['success' => TRUE, 'message' => "Unblocked user {$user->getDisplayName()}."];

      case 'add_role':
        if (empty($input['role'])) {
          return ['error' => 'Role is required for add_role action.'];
        }
        $user->addRole($input['role']);
        $user->save();
        return ['success' => TRUE, 'message' => "Added role '{$input['role']}' to {$user->getDisplayName()}."];

      case 'remove_role':
        if (empty($input['role'])) {
          return ['error' => 'Role is required for remove_role action.'];
        }
        $user->removeRole($input['role']);
        $user->save();
        return ['success' => TRUE, 'message' => "Removed role '{$input['role']}' from {$user->getDisplayName()}."];

      default:
        return ['error' => "Unknown action: {$input['action']}"];
    }
  }

  protected function listCommerceProducts(array $input): array {
    if (!$this->moduleHandler->moduleExists('commerce_product')) {
      return ['error' => 'Commerce Product module is not enabled.'];
    }

    $storage = $this->entityTypeManager->getStorage('commerce_product');
    $query = $storage->getQuery()->accessCheck(FALSE);

    if (!empty($input['product_type'])) {
      $query->condition('type', $input['product_type']);
    }
    if (!empty($input['store_id'])) {
      $query->condition('stores', $input['store_id']);
    }

    $limit = $input['limit'] ?? 25;
    $ids = $query->sort('changed', 'DESC')->range(0, $limit)->execute();
    $products = $storage->loadMultiple($ids);

    $results = [];
    foreach ($products as $product) {
      $results[] = [
        'id' => $product->id(),
        'type' => $product->bundle(),
        'title' => $product->getTitle(),
        'status' => $product->isPublished() ? 'published' : 'unpublished',
        'stores' => array_map(fn($s) => $s->id(), $product->getStores()),
      ];
    }
    return ['count' => count($results), 'products' => $results];
  }

  protected function listCommerceOrders(array $input): array {
    if (!$this->moduleHandler->moduleExists('commerce_order')) {
      return ['error' => 'Commerce Order module is not enabled.'];
    }

    $storage = $this->entityTypeManager->getStorage('commerce_order');
    $query = $storage->getQuery()->accessCheck(FALSE);

    if (!empty($input['state'])) {
      $query->condition('state', $input['state']);
    }
    if (!empty($input['order_type'])) {
      $query->condition('type', $input['order_type']);
    }

    $limit = $input['limit'] ?? 25;
    $ids = $query->sort('changed', 'DESC')->range(0, $limit)->execute();
    $orders = $storage->loadMultiple($ids);

    $results = [];
    foreach ($orders as $order) {
      $results[] = [
        'id' => $order->id(),
        'type' => $order->bundle(),
        'state' => $order->getState()->getId(),
        'total' => $order->getTotalPrice() ? $order->getTotalPrice()->getNumber() : '0',
        'currency' => $order->getTotalPrice() ? $order->getTotalPrice()->getCurrencyCode() : 'USD',
        'customer' => $order->getCustomer() ? $order->getCustomer()->getDisplayName() : 'guest',
        'placed' => $order->getPlacedTime() ? date('Y-m-d H:i:s', $order->getPlacedTime()) : NULL,
      ];
    }
    return ['count' => count($results), 'orders' => $results];
  }

  protected function getConfig(array $input): array {
    $config = $this->configFactory->get($input['name']);
    $data = $config->getRawData();
    if (empty($data)) {
      return ['error' => "Config '{$input['name']}' not found or empty."];
    }
    return ['name' => $input['name'], 'data' => $data];
  }

  protected function setConfig(array $input): array {
    $config = $this->configFactory->getEditable($input['name']);
    $config->set($input['key'], $input['value'])->save();
    return ['success' => TRUE, 'message' => "Set {$input['name']}:{$input['key']} = " . json_encode($input['value'])];
  }

  protected function clearCache(): array {
    drupal_flush_all_caches();
    return ['success' => TRUE, 'message' => 'All caches cleared.'];
  }

  protected function siteStatus(): array {
    $site_config = $this->configFactory->get('system.site');
    $node_count = $this->entityTypeManager->getStorage('node')
      ->getQuery()->accessCheck(FALSE)->count()->execute();
    $user_count = $this->entityTypeManager->getStorage('user')
      ->getQuery()->accessCheck(FALSE)->condition('uid', 0, '>')->count()->execute();

    $status = [
      'site_name' => $site_config->get('name'),
      'drupal_version' => \Drupal::VERSION,
      'php_version' => PHP_VERSION,
      'node_count' => $node_count,
      'user_count' => $user_count,
      'modules_enabled' => count($this->moduleHandler->getModuleList()),
    ];

    if ($this->moduleHandler->moduleExists('commerce_product')) {
      $status['product_count'] = $this->entityTypeManager->getStorage('commerce_product')
        ->getQuery()->accessCheck(FALSE)->count()->execute();
    }
    if ($this->moduleHandler->moduleExists('commerce_order')) {
      $status['order_count'] = $this->entityTypeManager->getStorage('commerce_order')
        ->getQuery()->accessCheck(FALSE)->count()->execute();
    }

    return $status;
  }

  protected function recentLogs(array $input): array {
    $severity_map = [
      'emergency' => 0, 'alert' => 1, 'critical' => 2, 'error' => 3,
      'warning' => 4, 'notice' => 5, 'info' => 6, 'debug' => 7,
    ];

    $query = $this->database->select('watchdog', 'w')
      ->fields('w', ['wid', 'type', 'severity', 'message', 'variables', 'timestamp'])
      ->orderBy('wid', 'DESC')
      ->range(0, $input['limit'] ?? 20);

    if (!empty($input['severity']) && isset($severity_map[$input['severity']])) {
      $query->condition('severity', $severity_map[$input['severity']]);
    }
    if (!empty($input['type'])) {
      $query->condition('type', $input['type']);
    }

    $results = [];
    foreach ($query->execute() as $row) {
      $variables = $row->variables ? @unserialize($row->variables) : [];
      $message = is_array($variables) ? strtr($row->message, $variables) : $row->message;
      $results[] = [
        'id' => $row->wid,
        'type' => $row->type,
        'severity' => array_search($row->severity, $severity_map) ?: $row->severity,
        'message' => strip_tags($message),
        'time' => date('Y-m-d H:i:s', $row->timestamp),
      ];
    }
    return ['count' => count($results), 'logs' => $results];
  }

  protected function sqlQuery(array $input): array {
    $query = trim($input['query']);
    if (!preg_match('/^\s*SELECT\s/i', $query)) {
      return ['error' => 'Only SELECT queries are allowed.'];
    }
    if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|GRANT|REVOKE)\b/i', $query)) {
      return ['error' => 'Write operations are not allowed.'];
    }

    $result = $this->database->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    return ['count' => count($result), 'rows' => array_slice($result, 0, 100)];
  }

  protected function manageModule(array $input): array {
    $installer = \Drupal::service('module_installer');

    if ($input['action'] === 'enable') {
      $installer->install([$input['module']]);
      return ['success' => TRUE, 'message' => "Module '{$input['module']}' enabled."];
    }
    elseif ($input['action'] === 'disable') {
      $installer->uninstall([$input['module']]);
      return ['success' => TRUE, 'message' => "Module '{$input['module']}' uninstalled."];
    }

    return ['error' => "Unknown action: {$input['action']}"];
  }

}
