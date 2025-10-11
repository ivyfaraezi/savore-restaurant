const itemCategory = document.getElementById("itemCategory");
const itemName = document.getElementById("itemName");
const priceInput = document.getElementById("price");
const quantityInput = document.getElementById("quantity");
const totalInput = document.getElementById("total");
const addToCartBtn = document.getElementById("addToCart");
const cartTableBody = document.querySelector("#cartTable tbody");
const submitOrderBtn = document.getElementById("submitOrderBtn");
let cart = [];

if (submitOrderBtn) submitOrderBtn.style.display = "none";

// Function to fetch items by category from database
async function fetchItemsByCategory(category) {
  try {
    const response = await fetch(
      `make-orders.php?action=getItems&category=${encodeURIComponent(category)}`
    );
    const items = await response.json();
    return items;
  } catch (error) {
    console.error("Error fetching items:", error);
    return [];
  }
}

itemCategory.addEventListener("change", async function () {
  const category = this.value;
  itemName.innerHTML = '<option value="">Select Item</option>';
  priceInput.value = "";
  totalInput.value = "";

  if (category) {
    itemName.innerHTML = '<option value="">Loading items...</option>';
    itemName.disabled = true;

    try {
      const items = await fetchItemsByCategory(category);
      itemName.innerHTML = '<option value="">Select Item</option>';
      itemName.disabled = false;

      if (items.length > 0) {
        items.forEach((item) => {
          const option = document.createElement("option");
          option.value = item.name;
          option.textContent = item.name;
          option.setAttribute("data-price", item.price);
          itemName.appendChild(option);
        });
      } else {
        itemName.innerHTML = '<option value="">No items available</option>';
      }
    } catch (error) {
      console.error("Error loading items:", error);
      itemName.innerHTML = '<option value="">Error loading items</option>';
      itemName.disabled = false;
      alert("Error loading items. Please try again.");
    }
  } else {
    itemName.disabled = false;
  }
});

itemName.addEventListener("change", function () {
  const selectedOption = itemName.options[itemName.selectedIndex];
  const price = selectedOption.getAttribute("data-price");
  priceInput.value = price ? price : "";
  updateTotal();
});

quantityInput.addEventListener("input", function () {
  updateTotal();
});

function getSelectedQuantity() {
  return parseInt(quantityInput.value) || 1;
}

function updateTotal() {
  const price = parseFloat(priceInput.value) || 0;
  const quantity = getSelectedQuantity();
  const total = price * quantity;
  totalInput.value = total.toFixed(2);
}

addToCartBtn.addEventListener("click", function () {
  const category = itemCategory.value;
  const item = itemName.value;
  const quantity = getSelectedQuantity();
  const price = parseFloat(priceInput.value) || 0;
  if (
    !document.getElementById("customerName").value.trim() ||
    !document.getElementById("email").value.trim() ||
    !category ||
    !item ||
    !price ||
    quantity < 1
  ) {
    alert("Please fill in all required fields before adding to cart.");
    return;
  }
  const total = price * quantity;
  cart.push({
    category,
    item,
    quantity,
    price,
    total: total.toFixed(2),
  });
  renderCart();
  itemName.selectedIndex = 0;
  priceInput.value = "";
  quantityInput.value = 1;
  totalInput.value = "";
});

function renderCart() {
  cartTableBody.innerHTML = "";
  cart.forEach((cartItem, idx) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${cartItem.item}</td>
      <td>${cartItem.category.replace("-", " ")}</td>
      <td>${cartItem.quantity}</td>
      <td>${cartItem.price}</td>
      <td>${cartItem.total}</td>
      <td><button class="cart-remove-btn" data-idx="${idx}" title="Remove">&#10006;</button></td>
    `;
    cartTableBody.appendChild(row);
  });
  if (submitOrderBtn) {
    submitOrderBtn.style.display = cart.length > 0 ? "block" : "none";
  }
  document.querySelectorAll(".cart-remove-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const removeIdx = parseInt(this.getAttribute("data-idx"));
      cart.splice(removeIdx, 1);
      renderCart();
    });
  });
}

if (submitOrderBtn) {
  submitOrderBtn.addEventListener("click", async function (e) {
    e.preventDefault();
    if (cart.length === 0) {
      alert("Cart is empty! Please add items to cart before submitting.");
      return;
    }
    const customerName = document.getElementById("customerName").value.trim();
    const email = document.getElementById("email").value.trim();
    const mobile = document.getElementById("mobile").value.trim();
    if (!customerName || !email || !mobile) {
      alert("Please fill in all customer information fields.");
      return;
    }
    submitOrderBtn.disabled = true;
    submitOrderBtn.textContent = "Processing...";

    try {
      const formData = new FormData();
      formData.append("action", "submitOrder");
      formData.append("customerName", customerName);
      formData.append("email", email);
      formData.append("mobile", mobile);
      formData.append("cartData", JSON.stringify(cart));
      const response = await fetch("make-orders.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        let grandTotal = 0;
        cart.forEach((item) => {
          grandTotal += parseFloat(item.total);
        });

        alert(
          `Order submitted successfully!\nOrder ID: ${
            result.orderId
          }\nTotal amount: ${grandTotal.toFixed(2)}`
        );
        cart = [];
        renderCart();
        document.getElementById("customerName").value = "";
        document.getElementById("email").value = "";
        document.getElementById("mobile").value = "";
        document.getElementById("itemCategory").selectedIndex = 0;
        document.getElementById("itemName").selectedIndex = 0;
        document.getElementById("price").value = "";
        document.getElementById("total").value = "";
        document.getElementById("quantity").value = 1;
      } else {
        alert(`Error: ${result.message}`);
      }
    } catch (error) {
      console.error("Error submitting order:", error);
      alert("Error submitting order. Please try again.");
    } finally {
      submitOrderBtn.disabled = false;
      submitOrderBtn.textContent = "Submit Order";
    }
  });
}
