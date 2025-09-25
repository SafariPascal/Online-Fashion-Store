<?php

$config = [
    'db_host' => 'localhost',
    'db_name' => 'online-fashion-store',
    'db_user' => 'root',
    'db_pass' => '1224'
];


function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

class DB {
    private static $pdo;
    public static function init($c) {
        if (self::$pdo) return;
        $dsn = "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";
        self::$pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    public static function pdo() { return self::$pdo; }
}

DB::init($config);
$pdo = DB::pdo();


function place_order($data) {
    global $pdo;
    // Expected body: {customer_email, items: [{product_name, quantity, unit_price}]}
    $email = $data['customer_email'] ?? null;
    $items = $data['items'] ?? [];
    if (!$email || !is_array($items) || count($items) == 0) json_response(['error' => 'Missing params'], 400);

    try {
        $pdo->beginTransaction();
        $total = 0.0;

        // Create order
        $stmt = $pdo->prepare('INSERT INTO orders (customer_email, total, status) VALUES (:email, 0, "confirmed")');
        $stmt->execute([':email' => $email]);
        $order_id = $pdo->lastInsertId();

        foreach ($items as $it) {
            $name = $it['product_name'];
            $q = (int)$it['quantity'];
            $price = (float)$it['unit_price'];
            if ($q <= 0 || $price < 0) { $pdo->rollBack(); json_response(['error' => 'Invalid item data'], 400); }

            $insItem = $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_price) VALUES (:oid, :name, :q, :p)');
            $insItem->execute([':oid' => $order_id, ':name' => $name, ':q' => $q, ':p' => $price]);
            $total += $price * $q;
        }

        // Update order total
        $upd = $pdo->prepare('UPDATE orders SET total = :t WHERE id = :id');
        $upd->execute([':t' => $total, ':id' => $order_id]);

        $pdo->commit();
        json_response(['order_id' => $order_id, 'total' => number_format($total, 2)], 201);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_response(['error' => $e->getMessage()], 500);
    }
}

function list_orders() {
    global $pdo;
    $sql = 'SELECT o.id, o.customer_email, o.total, o.status, o.created_at, i.product_name, i.quantity, i.unit_price
            FROM orders o
            LEFT JOIN order_items i ON o.id = i.order_id
            ORDER BY o.id DESC';
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    // group items by order
    $orders = [];
    foreach ($rows as $r) {
        $oid = $r['id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'id' => $oid,
                'customer_email' => $r['customer_email'],
                'total' => $r['total'],
                'status' => $r['status'],
                'created_at' => $r['created_at'],
                'items' => []
            ];
        }
        $orders[$oid]['items'][] = [
            'product_name' => $r['product_name'],
            'quantity' => $r['quantity'],
            'unit_price' => $r['unit_price']
        ];
    }
    json_response(['orders' => array_values($orders)]);
}


// Router

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$body = null;
if (in_array($method, ['POST','PUT','PATCH'])) {
    $input = file_get_contents('php://input');
    $body = json_decode($input, true);
}

switch (true) {
    case $method === 'POST' && $uri === '/order':
        place_order($body ?? []);
        break;
    case $method === 'GET' && $uri === '/orders':
        list_orders();
        break;
    default:
        json_response(['error' => 'Not Found: ' . $method . ' ' . $uri], 404);
}

?>
