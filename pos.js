document.addEventListener('DOMContentLoaded', function() {
    let cart = [];
    const cartItemsContainer = document.getElementById('cart-items-container');
    const cartItemsInput = document.getElementById('cart_items');
    const subtotalElement = document.getElementById('subtotal');
    const taxElement = document.getElementById('tax');
    const totalElement = document.getElementById('total');
    const discountRow = document.getElementById('discount_row');
    const discountAmountElement = document.getElementById('discount_amount');
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const productGrid = document.getElementById('product-grid');
    const barcodeInput = document.getElementById('barcode-input');
    const scanBtn = document.getElementById('scan-btn');
    
    // Global variables for open product handling
    let currentOpenProduct = null;
    let currentPricePerKg = 0;
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            addProductToCart(this.parentElement);
        });
    });
    
    // Search functionality
    searchBtn.addEventListener('click', searchProducts);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    // Barcode functionality
    scanBtn.addEventListener('click', function() {
        const barcode = barcodeInput.value.trim();
        if (barcode) {
            findProductByBarcode(barcode);
            barcodeInput.value = '';
        }
    });
    
    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const barcode = barcodeInput.value.trim();
            if (barcode) {
                findProductByBarcode(barcode);
                barcodeInput.value = '';
            }
        }
    });
    
    // Event listeners for payment and discount fields
    document.getElementById('cash_given').addEventListener('input', calculateChange);
    document.getElementById('discount_value').addEventListener('input', updateCart);
    document.getElementById('tax_rate').addEventListener('input', updateCart);
    
    // Function to add product to cart
    function addProductToCart(productItem) {
        const productId = productItem.getAttribute('data-id');
        const productName = productItem.getAttribute('data-name');
        const productPrice = parseFloat(productItem.getAttribute('data-price'));
        const stock = parseInt(productItem.getAttribute('data-stock'));
        const isOpenProduct = productItem.getAttribute('data-open') === '1';
        const productImage = productItem.querySelector('img') ? productItem.querySelector('img').src : '';
        
        // For open products, show weight modal
        if (isOpenProduct) {
            openWeightModal(productItem);
            return;
        }
        
        // Check if product is already in cart
        const existingItem = cart.find(item => item.id === productId && !item.is_open_product);
        
        if (existingItem) {
            if (existingItem.quantity < stock) {
                existingItem.quantity++;
                updateCart();
            } else {
                alert('Not enough stock available');
            }
        } else {
            if (stock > 0) {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: 1,
                    image: productImage,
                    is_open_product: false
                });
                updateCart();
            } else {
                alert('Product is out of stock');
            }
        }
    }
    
    // Function to search products
    function searchProducts() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const productItems = productGrid.querySelectorAll('.product-item');
        
        productItems.forEach(item => {
            const productName = item.getAttribute('data-name').toLowerCase();
            const productId = item.getAttribute('data-id').toLowerCase();
            const barcode = item.getAttribute('data-barcode').toLowerCase();
            
            if (productName.includes(searchTerm) ||
                productId.includes(searchTerm) ||
                barcode.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // Function to find product by barcode
    function findProductByBarcode(barcode) {
        const productItems = productGrid.querySelectorAll('.product-item');
        let found = false;
        
        productItems.forEach(item => {
            const itemBarcode = item.getAttribute('data-barcode');
            if (itemBarcode === barcode) {
                addProductToCart(item);
                found = true;
                // Show all products after successful barcode scan
                productItems.forEach(i => i.style.display = 'block');
                searchInput.value = '';
            }
        });
        
        if (!found) {
            alert('No product found with this barcode');
        }
    }
    
    // Open weight modal for open products
    function openWeightModal(productItem) {
        currentOpenProduct = productItem;
        currentPricePerKg = parseFloat(productItem.getAttribute('data-price-per-kg'));
        
        // Reset inputs
        document.getElementById('weight_kg').value = '0';
        document.getElementById('weight_grams').value = '0';
        document.getElementById('price_input').value = '0.00';
        
        // Set price per kg display
        document.getElementById('price_per_kg_display').textContent = '$' + currentPricePerKg.toFixed(2);
        
        // Update calculation
        updateOpenProductCalculation();
        
        // Show modal
        document.getElementById('openProductModal').style.display = 'block';
    }
    
    // Update open product calculation
    function updateOpenProductCalculation() {
        const kg = parseFloat(document.getElementById('weight_kg').value) || 0;
        const grams = parseFloat(document.getElementById('weight_grams').value) || 0;
        const totalWeight = kg + (grams / 1000);
        const calculatedPrice = totalWeight * currentPricePerKg;
        
        // Update price field
        document.getElementById('price_input').value = calculatedPrice.toFixed(2);
        
        // Update display
        document.getElementById('total_weight').textContent = totalWeight.toFixed(3);
        document.getElementById('total_price').textContent = calculatedPrice.toFixed(2);
    }
    
    // Add product with weight to cart
    document.getElementById('add_open_product').addEventListener('click', function() {
        const kg = parseFloat(document.getElementById('weight_kg').value) || 0;
        const grams = parseFloat(document.getElementById('weight_grams').value) || 0;
        const totalWeight = kg + (grams / 1000);
        
        if (totalWeight <= 0) {
            alert('Please enter a valid weight');
            return;
        }
        
        const productId = currentOpenProduct.getAttribute('data-id');
        const productName = currentOpenProduct.getAttribute('data-name');
        const calculatedPrice = totalWeight * currentPricePerKg;
        const productImage = currentOpenProduct.querySelector('img') ? 
                            currentOpenProduct.querySelector('img').src : '';
        
        // Add to cart with weight
        cart.push({
            id: productId,
            name: productName,
            price: calculatedPrice,
            quantity: 1,
            weight: totalWeight,
            price_per_kg: currentPricePerKg,
            image: productImage,
            is_open_product: true
        });
        
        updateCart();
        
        // Close modal
        document.getElementById('openProductModal').style.display = 'none';
    });
    
    // Event listeners for weight inputs
    document.getElementById('weight_kg').addEventListener('input', updateOpenProductCalculation);
    document.getElementById('weight_grams').addEventListener('input', updateOpenProductCalculation);
    
    // Update cart display - Enhanced version with better weight display
    function updateCart() {
        // Clear cart container
        cartItemsContainer.innerHTML = '';
        
        // Add items to cart
        cart.forEach((item, index) => {
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            
            let itemDetails = `
                ${item.image ? `<img src="${item.image}" alt="${item.name}" class="cart-item-image">` : 
                `<div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px; margin-right: 15px;">
                    <i class="fas fa-image" style="color: #ccc;"></i>
                </div>`}
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
            `;
            
            if (item.is_open_product) {
                // Display weight information for open products in kg and grams
                const kg = Math.floor(item.weight);
                const grams = Math.round((item.weight - kg) * 1000);
                
                itemDetails += `
                    <div class="cart-item-price">$${item.price.toFixed(2)} (${kg} kg ${grams} g @ $${item.price_per_kg.toFixed(2)}/kg)</div>
                `;
            } else {
                itemDetails += `
                    <div class="cart-item-price">$${item.price.toFixed(2)} each</div>
                `;
            }
            
            itemDetails += `</div>`;
            
            if (item.is_open_product) {
                itemDetails += `
                    <div class="cart-item-controls">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(${index})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
            } else {
                itemDetails += `
                    <div class="cart-item-controls">
                        <button type="button" class="quantity-btn" onclick="decreaseQuantity(${index})">-</button>
                        <span>${item.quantity}</span>
                        <button type="button" class="quantity-btn" onclick="increaseQuantity(${index})">+</button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(${index})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
            }
            
            cartItem.innerHTML = itemDetails;
            cartItemsContainer.appendChild(cartItem);
        });
        
        // Calculate totals
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // Apply discount
        const discountType = document.getElementById('discount_type').value;
        const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;
        let discountAmount = 0;
        
        if (discountType === 'amount') {
            discountAmount = Math.min(discountValue, subtotal);
        } else if (discountType === 'percentage') {
            discountAmount = subtotal * (discountValue / 100);
        }
        
        const taxableAmount = subtotal - discountAmount;
        const taxRate = parseFloat(document.getElementById('tax_rate').value) || 10;
        const tax = taxableAmount * (taxRate / 100);
        const total = taxableAmount + tax;
        
        // Update total displays
        subtotalElement.textContent = '$' + subtotal.toFixed(2);
        
        if (discountAmount > 0) {
            discountRow.style.display = 'table-row';
            discountAmountElement.textContent = '-$' + discountAmount.toFixed(2);
        } else {
            discountRow.style.display = 'none';
        }
        
        taxElement.textContent = '$' + tax.toFixed(2);
        totalElement.textContent = '$' + total.toFixed(2);
        
        // Calculate change if payment method is cash
        if (document.getElementById('payment_method').value === 'Cash') {
            calculateChange();
        }
        
        // Update hidden input for form submission
        cartItemsInput.value = JSON.stringify(cart);
    }
    
    // Calculate change for cash payment
    function calculateChange() {
        const total = parseFloat(totalElement.textContent.substring(1));
        const cashGiven = parseFloat(document.getElementById('cash_given').value) || 0;
        const change = cashGiven - total;
        
        document.getElementById('change_amount').textContent = '$' + (change > 0 ? change.toFixed(2) : '0.00');
    }
    
    // Global functions for cart controls
    window.increaseQuantity = function(index) {
        const item = cart[index];
        if (!item.is_open_product) {
            const stock = parseInt(document.querySelector(`.product-item[data-id="${item.id}"]`).getAttribute('data-stock'));
            
            if (item.quantity < stock) {
                item.quantity++;
                updateCart();
            } else {
                alert('Not enough stock available');
            }
        }
    };
    
    window.decreaseQuantity = function(index) {
        const item = cart[index];
        if (!item.is_open_product && item.quantity > 1) {
            item.quantity--;
            updateCart();
        }
    };
    
    window.removeFromCart = function(index) {
        cart.splice(index, 1);
        updateCart();
    };
    
    // Initialize cart
    updateCart();
});