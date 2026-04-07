<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('access_pos');

// Handle Transaction Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale'])) {
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $items = json_decode($_POST['items'], true);
    $total_amount = $_POST['total_amount'];
    $payment_type = $_POST['payment_type'];
    $invoice_no = "INV-" . date('YmdHis') . rand(10, 99);
    $user_id = $_SESSION['user_id'];

    if (!empty($items)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert Sales
            $stmt = $pdo->prepare("INSERT INTO sales (user_id, customer_id, invoice_no, total_amount, payment_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $customer_id, $invoice_no, $total_amount, $payment_type]);
            $sale_id = $pdo->lastInsertId();

            // 2. Insert Details & Update Stock
            $stmt_detail = $pdo->prepare("INSERT INTO sales_details (sale_id, product_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($items as $item) {
                $stmt_detail->execute([$sale_id, $item['id'], $item['qty'], $item['price'], $item['subtotal']]);
                $stmt_stock->execute([$item['qty'], $item['id']]);
            }

            $pdo->commit();
            
            // Output script to open print window and redirect
            echo "<script>
                    window.open('print_invoice.php?id=$sale_id', '_blank', 'width=400,height=600');
                    window.location.href = 'pos.php?success=1&inv=$invoice_no';
                  </script>";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage("Transaction Failed: " . $e->getMessage(), "danger");
        }
    }
}

// Fetch Master Data for POS
$products = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY name ASC")->fetchAll();
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC")->fetchAll();

if (isset($_GET['success'])) {
    setFlashMessage("Transaction Success! Invoice: " . ($_GET['inv'] ?? ''));
}

include 'layouts/header.php';
?>

<div class="row g-3">
    <!-- Product Selection (Left) -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header border-0 bg-transparent py-3">
                <div class="input-group">
                    <span class="input-group-text border-0 bg-transparent ps-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="productSearch" class="form-control border-0 shadow-none bg-transparent" placeholder="Search products...">
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3" id="productList">
                    <?php foreach ($products as $p): ?>
                        <div class="col-6 col-lg-4 product-item" data-name="<?php echo strtolower($p['name']); ?>">
                            <div class="card h-100 product-card p-3 text-center" 
                                 onclick='addToCart(<?php echo json_encode($p); ?>)'>
                                <div class="mb-3">
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary mx-auto mb-0" style="width: 60px; height: 60px; font-size: 2rem;">
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                                <h6 class="mb-1 text-truncate fw-bold"><?php echo $p['name']; ?></h6>
                                <div class="text-primary fw-bold mb-2"><?php echo formatRupiah($p['price']); ?></div>
                                <div class="badge bg-secondary bg-opacity-10 text-muted rounded-pill small">Stock: <?php echo $p['stock']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart (Right) -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 py-3 fw-bold d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i> Current Order</h5>
                <span id="itemCount" class="badge bg-primary rounded-pill px-3">0 Items</span>
            </div>
            <div class="card-body p-0">
                <div id="cartItems" style="height: 380px; overflow-y: auto;" class="px-3 border-top pt-3">
                    <div class="text-center text-muted mt-5 empty-cart-msg">
                        <i class="fas fa-shopping-basket fa-3x mb-3 opacity-25"></i>
                        <p>Your cart is empty</p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 p-4">
                <form method="POST" id="posForm">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Select Customer</label>
                        <select name="customer_id" class="form-select border-0 shadow-sm py-2 px-3">
                            <option value="">-- Walk-in Customer --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span id="disp-subtotal" class="fw-bold">Rp 0</span>
                    </div>
                    <hr class="my-3 opacity-10">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="fw-bold mb-0">Total</h4>
                        <h4 id="disp-total" class="fw-bold text-primary mb-0">Rp 0</h4>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Payment Method</label>
                        <select name="payment_type" id="payment_type" class="form-select border-0 shadow-sm py-2 px-3">
                            <option value="cash">Cash (Tunai)</option>
                            <option value="card">Card (Kartu)</option>
                            <option value="credit">Account Credit (Piutang)</option>
                            <option value="transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <input type="hidden" name="items" id="itemsInput">
                    <input type="hidden" name="total_amount" id="totalInput">
                    <input type="hidden" name="submit_sale" value="1">

                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm mb-2" onclick="validateAndSubmit()">
                        PROCESS PAYMENT <i class="fas fa-check-circle ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .product-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        border: 1px solid var(--border-color) !important;
    }
    .product-card:hover {
        border-color: var(--primary-color) !important;
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(118, 75, 162, 0.1);
    }
    .cart-item {
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
</style>

<script>
let cart = [];

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        if (existing.qty < product.stock) {
            existing.qty++;
            existing.subtotal = existing.qty * existing.price;
        } else {
            alert('Out of stock!');
            return;
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            qty: 1,
            stock: product.stock,
            subtotal: parseFloat(product.price)
        });
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

function updateQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (item) {
        let newQty = item.qty + delta;
        if (newQty > 0 && newQty <= item.stock) {
            item.qty = newQty;
            item.subtotal = item.qty * item.price;
            renderCart();
        } else if (newQty <= 0) {
            removeFromCart(id);
        }
    }
}

function renderCart() {
    const cartBox = document.getElementById('cartItems');
    const itemCountSpan = document.getElementById('itemCount');
    
    if (cart.length === 0) {
        cartBox.innerHTML = `
            <div class="text-center text-muted mt-5 empty-cart-msg">
                <i class="fas fa-shopping-basket fa-3x mb-3 opacity-25"></i>
                <p>Your cart is empty</p>
            </div>
        `;
        itemCountSpan.innerText = '0 Items';
        updateTotals();
        return;
    }

    itemCountSpan.innerText = `${cart.length} Items`;
    let html = '';
    cart.forEach(item => {
        html += `
            <div class="cart-item d-flex justify-content-between align-items-center">
                <div style="max-width: 60%">
                    <div class="fw-bold text-truncate">${item.name}</div>
                    <small class="text-muted">${formatMoney(item.price)} x ${item.qty}</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="btn-group btn-group-sm me-2">
                        <button class="btn btn-outline-secondary" onclick="updateQty(${item.id}, -1)">-</button>
                        <button class="btn btn-outline-secondary" disabled>${item.qty}</button>
                        <button class="btn btn-outline-secondary" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                    <button class="btn btn-sm text-danger" onclick="removeFromCart(${item.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    cartBox.innerHTML = html;
    updateTotals();
}

function updateTotals() {
    const total = cart.reduce((sum, item) => sum + item.subtotal, 0);
    document.getElementById('disp-subtotal').innerText = formatMoney(total);
    document.getElementById('disp-total').innerText = formatMoney(total);
    document.getElementById('totalInput').value = total;
    document.getElementById('itemsInput').value = JSON.stringify(cart);
}

function formatMoney(amount) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount).replace('IDR', 'Rp');
}

function validateAndSubmit() {
    if (cart.length === 0) {
        alert('Empty cart!');
        return;
    }
    if (confirm('Proceed to checkout?')) {
        document.getElementById('posForm').submit();
    }
}

// Search Functionality
document.getElementById('productSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(el => {
        const name = el.getAttribute('data-name');
        el.style.display = name.includes(term) ? 'block' : 'none';
    });
});
</script>

<?php include 'layouts/footer.php'; ?>
