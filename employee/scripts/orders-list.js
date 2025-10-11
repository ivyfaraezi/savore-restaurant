document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".save-status-btn").forEach(function (saveBtn) {
    saveBtn.addEventListener("click", function () {
      const row = saveBtn.closest("tr");
      const orderId = row.getAttribute("data-order-id");
      const select = row.querySelector(".status-select");
      const statusCell = row.querySelector("td:nth-child(6)");

      if (!select || !statusCell || !orderId) {
        alert("Error: Unable to find required elements");
        return;
      }

      const selectedValue = select.value;
      const selectedText = select.options[select.selectedIndex].text;

      saveBtn.disabled = true;
      saveBtn.textContent = "Saving...";

      fetch("orders-list.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          ajax_action: "update_status",
          order_id: parseInt(orderId),
          status: selectedValue,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            statusCell.innerHTML = `<span class="${data.status_class}">${data.new_status}</span>`;
            showMessage("Status Updated Successfully!", "success");
          } else {
            showMessage("Error: " + data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showMessage("Network error occurred. Please try again.", "error");
        })
        .finally(() => {
          saveBtn.disabled = false;
          saveBtn.textContent = "Save";
        });
    });
  });

  // Function to show messages to user
  function showMessage(message, type) {
    const existingMessage = document.querySelector(".status-message");
    if (existingMessage) {
      existingMessage.remove();
    }
    const messageDiv = document.createElement("div");
    messageDiv.className = `status-message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            ${
              type === "success"
                ? "background-color: #4CAF50;"
                : "background-color: #f44336;"
            }
        `;

    document.body.appendChild(messageDiv);
    setTimeout(() => {
      messageDiv.remove();
    }, 3000);
  }
});
