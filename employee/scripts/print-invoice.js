document.addEventListener("click", function (e) {
  if (e.target && e.target.classList.contains("print-invoice-btn")) {
    const btn = e.target;
    const row = btn.closest("tr");
    const billId = row.children[0].textContent.trim();

    fetch(`../api/get_order_details.php?orderId=${billId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          generateProfessionalInvoice(data.order);
        } else {
          alert("Error fetching order details: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Failed to fetch order details. Please try again.");
      });
  }
});

function generateProfessionalInvoice(order) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  doc.setFont("helvetica");
  const primaryColor = [25, 118, 210]; // #1976d2
  const secondaryColor = [0, 188, 212]; // #00bcd4
  const darkColor = [51, 51, 51]; // #333
  const lightGray = [245, 245, 245];
  const billId = order.id.toString().padStart(3, "0");
  const customerName = order.customerName || "Walk-in Customer";
  const customerEmail = order.customerEmail || "";
  const customerMobile = order.customerMobile || "";
  const itemsList = order.items;
  const actualTotal = order.total;

  // Header Background
  doc.setFillColor(...primaryColor);
  doc.rect(0, 0, 210, 50, "F");

  // Restaurant Name
  doc.setFont("helvetica", "bold");
  doc.setFontSize(26);
  doc.setTextColor(255, 255, 255);
  doc.text("Savoré Restaurant", 20, 25);

  // Tagline
  doc.setFont("helvetica", "normal");
  doc.setFontSize(10);
  doc.text("Fine Dining Experience", 20, 32);

  // Restaurant Details
  doc.setFontSize(9);
  doc.text("123 Savoré Street, Dhaka , Bangladesh", 20, 38);
  doc.text(
    "+880-1992346336 | +880-1857048383 |  savore.2006@gmail.com",
    20,
    43
  );

  // Invoice Title Box
  doc.setFillColor(...secondaryColor);
  doc.roundedRect(140, 10, 50, 15, 2, 2, "F");
  doc.setTextColor(255, 255, 255);
  doc.setFont("helvetica", "bold");
  doc.setFontSize(14);
  doc.text("INVOICE", 165, 20, { align: "center" });

  // Invoice Details Box
  doc.setTextColor(...darkColor);
  doc.setFillColor(...lightGray);
  doc.roundedRect(140, 28, 50, 20, 2, 2, "F");

  doc.setFont("helvetica", "bold");
  doc.setFontSize(9);
  doc.text("Invoice #:", 142, 34);
  doc.text("Date:", 142, 39);
  doc.text("Time:", 142, 44);

  doc.setFont("helvetica", "normal");
  doc.text(billId, 165, 34);
  doc.text(new Date().toLocaleDateString(), 165, 39);
  doc.text(new Date().toLocaleTimeString(), 165, 44);

  // Customer Section
  let yPos = 65;
  doc.setFont("helvetica", "bold");
  doc.setFontSize(12);
  doc.text("BILL TO:", 20, yPos);

  yPos += 8;
  doc.setFont("helvetica", "normal");
  doc.setFontSize(10);
  doc.text(customerName, 20, yPos);
  if (customerEmail) {
    doc.text(`Email: ${customerEmail}`, 20, yPos + 5);
    yPos += 5;
  }
  if (customerMobile) {
    doc.text(`Phone: ${customerMobile}`, 20, yPos + 5);
    yPos += 5;
  }
  doc.text(`Order ID: ${billId}`, 20, yPos + 5);

  // Items Table Header
  yPos += 15;
  doc.setFillColor(...primaryColor);
  doc.rect(20, yPos, 170, 12, "F");

  doc.setTextColor(255, 255, 255);
  doc.setFont("helvetica", "bold");
  doc.setFontSize(10);
  doc.text("ITEM DESCRIPTION", 25, yPos + 8);
  doc.text("QTY", 140, yPos + 8, { align: "center" });
  doc.text("UNIT PRICE", 160, yPos + 8, { align: "center" });
  doc.text("AMOUNT", 180, yPos + 8, { align: "center" });

  // Parse and display items
  yPos += 15;
  doc.setTextColor(...darkColor);
  doc.setFont("helvetica", "normal");
  doc.setFontSize(9);

  let itemCount = 0;

  // Calculate total quantity for all items
  itemsList.forEach((item) => {
    itemCount += parseInt(item.quantity);
  });

  // Display items with actual prices from database
  if (itemsList.length > 0) {
    itemsList.forEach((item, index) => {
      if (yPos > 250) {
        doc.addPage();
        yPos = 30;
      }

      if (index % 2 === 0) {
        doc.setFillColor(248, 249, 250);
        doc.rect(20, yPos - 4, 170, 10, "F");
      }

      doc.text(item.name, 25, yPos + 2);
      doc.text(item.quantity.toString(), 140, yPos + 2, { align: "center" });
      doc.text("Tk " + parseFloat(item.unitPrice).toFixed(2), 160, yPos + 2, {
        align: "center",
      });
      doc.text("Tk " + parseFloat(item.amount).toFixed(2), 180, yPos + 2, {
        align: "center",
      });

      yPos += 10;
    });
  }

  // Total Section
  yPos += 10;
  doc.setLineWidth(1);
  doc.line(120, yPos, 190, yPos);

  yPos += 8;
  doc.setFillColor(...primaryColor);
  doc.rect(120, yPos - 4, 70, 12, "F");

  doc.setTextColor(255, 255, 255);
  doc.setFont("helvetica", "bold");
  doc.setFontSize(12);
  doc.text("TOTAL:", 140, yPos + 4);
  doc.text("Tk " + parseFloat(actualTotal).toFixed(2), 180, yPos + 4, {
    align: "right",
  });

  // Order Summary Box
  yPos += 25;
  doc.setTextColor(...darkColor);
  doc.setFillColor(240, 245, 255);
  doc.roundedRect(20, yPos, 170, 25, 3, 3, "F");

  doc.setFont("helvetica", "bold");
  doc.setFontSize(10);
  doc.text("ORDER SUMMARY", 25, yPos + 8);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9);
  doc.text(`Total Items: ${itemCount}`, 25, yPos + 15);
  doc.text(`Payment Method: Cash`, 25, yPos + 20);
  doc.text(`Server: Restaurant Staff`, 100, yPos + 15);
  // Footer
  yPos += 35;
  doc.setLineWidth(0.3);
  doc.line(20, yPos, 190, yPos);

  yPos += 10;
  doc.setFont("helvetica", "bold");
  doc.setFontSize(12);
  doc.setTextColor(...primaryColor);
  doc.text("Thank you for dining with us!", 105, yPos, { align: "center" });

  yPos += 8;
  doc.setFont("helvetica", "normal");
  doc.setFontSize(9);
  doc.setTextColor(...darkColor);
  doc.text("We hope you enjoyed your meal. Please visit us again!", 105, yPos, {
    align: "center",
  });

  yPos += 5;
  doc.text("Follow us on social media @SavoréRestaurant", 105, yPos, {
    align: "center",
  });

  yPos += 8;
  doc.setFont("helvetica", "normal");
  doc.setFontSize(8);
  doc.text(
    "This is a computer-generated invoice. No signature required.",
    105,
    yPos,
    { align: "center" }
  );

  doc.save(
    `Savore_Invoice_${billId}_${new Date().toISOString().split("T")[0]}.pdf`
  );
}
