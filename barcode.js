// Barcode scanner functionality
class BarcodeScanner {
    constructor() {
        this.barcode = '';
        this.timeout = null;
        this.barcodeInput = document.getElementById('barcode-input');
        
        // Listen for keypress events
        document.addEventListener('keypress', this.handleKeyPress.bind(this));
    }
    
    handleKeyPress(e) {
        // If the barcode input is focused, let it handle the input
        if (document.activeElement === this.barcodeInput) {
            return;
        }
        
        // Clear previous timeout
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        
        // Append the character to the barcode
        this.barcode += e.key;
        
        // Set a timeout to detect when the barcode is complete
        this.timeout = setTimeout(() => {
            this.processBarcode();
        }, 100); // 100ms delay between characters
    }
    
    processBarcode() {
        // If barcode is not empty, try to find the product
        if (this.barcode.length > 3) { // Minimum barcode length
            this.findProductByBarcode(this.barcode);
        }
        
        // Reset the barcode
        this.barcode = '';
    }
    
    findProductByBarcode(barcode) {
        const productItems = document.querySelectorAll('.product-item');
        let found = false;
        
        productItems.forEach(item => {
            const itemBarcode = item.getAttribute('data-barcode');
            if (itemBarcode === barcode) {
                // Simulate click on the add to cart button
                const addButton = item.querySelector('.add-to-cart');
                if (addButton) {
                    addButton.click();
                    found = true;
                }
            }
        });
        
        if (!found) {
            console.log('No product found with barcode:', barcode);
            // Optionally, you could show a notification to the user
        }
    }
}

// Initialize barcode scanner when the page loads
document.addEventListener('DOMContentLoaded', function() {
    new BarcodeScanner();
});