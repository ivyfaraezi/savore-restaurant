document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("booking-form");
  const tableBody = document.getElementById("reservations-tbody");
  const actionInput = document.getElementById("action");
  const reservationIdInput = document.getElementById("reservation-id");
  const bookBtn = document.getElementById("book-btn");
  const updateBtn = document.getElementById("update-btn");
  const cancelBtn = document.getElementById("cancel-btn");
  const tableSelect = document.getElementById("table-no");

  let selectedRow = null;
  let originalData = null;
  let tablesData = [];
  const timeDisplay = document.getElementById("time-display");
  const timeHidden = document.getElementById("time");
  const timeDropdown = document.getElementById("time-dropdown");
  const timeOptions = document.getElementById("time-options");

  function generateTimeOptions() {
    const times = [];
    for (let hour = 9; hour <= 23; hour++) {
      for (let minute = 0; minute < 60; minute += 30) {
        const hourStr = hour.toString().padStart(2, "0");
        const minuteStr = minute.toString().padStart(2, "0");
        const time24 = `${hourStr}:${minuteStr}`;
        const period = hour >= 12 ? "PM" : "AM";
        const hour12 = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
        const time12 = `${hour12}:${minuteStr} ${period}`;

        times.push({ value: time24, display: time12 });
      }
    }
    return times;
  }

  function populateTimeOptions() {
    const times = generateTimeOptions();
    timeOptions.innerHTML = "";

    times.forEach((time) => {
      const option = document.createElement("div");
      option.className = "time-option";
      option.textContent = time.display;
      option.dataset.value = time.value;

      option.addEventListener("click", function () {
        timeDisplay.value = time.display;
        timeHidden.value = time.value;
        document.querySelectorAll(".time-option").forEach((opt) => {
          opt.classList.remove("selected");
        });
        option.classList.add("selected");
        timeDropdown.classList.remove("show");
      });

      timeOptions.appendChild(option);
    });
  }

  timeDisplay.addEventListener("click", function (e) {
    e.stopPropagation();
    timeDropdown.classList.toggle("show");
    const selectedOption = timeOptions.querySelector(".time-option.selected");
    if (selectedOption) {
      selectedOption.scrollIntoView({ block: "center" });
    }
  });

  document.addEventListener("click", function (e) {
    if (!timeDisplay.contains(e.target) && !timeDropdown.contains(e.target)) {
      timeDropdown.classList.remove("show");
    }
  });

  timeDropdown.addEventListener("click", function (e) {
    e.stopPropagation();
  });
  populateTimeOptions();
  const dateDisplay = document.getElementById("date-display");
  const dateHidden = document.getElementById("date");
  const dateCalendar = document.getElementById("date-picker-calendar");
  const calendarDays = document.getElementById("calendar-days");
  const calendarMonthYear = document.getElementById("calendar-month-year");
  const prevMonthBtn = document.getElementById("prev-month");
  const nextMonthBtn = document.getElementById("next-month");

  let currentDate = new Date();
  let selectedDate = null;

  // Format date as YYYY-MM-DD
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  // Format date for display (e.g., "Jan 15, 2025")
  function formatDisplayDate(date) {
    const months = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
    return `${
      months[date.getMonth()]
    } ${date.getDate()}, ${date.getFullYear()}`;
  }

  // Generate calendar for current month
  function generateCalendar(year, month) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const prevLastDay = new Date(year, month, 0);
    const firstDayIndex = firstDay.getDay();
    const lastDayIndex = lastDay.getDay();
    const nextDays = 7 - lastDayIndex - 1;

    const months = [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December",
    ];

    calendarMonthYear.textContent = `${months[month]} ${year}`;

    let days = "";

    // Previous month's days
    for (let x = firstDayIndex; x > 0; x--) {
      const day = prevLastDay.getDate() - x + 1;
      days += `<div class="calendar-day empty other-month">${day}</div>`;
    }

    // Current month's days
    const today = new Date();
    const todayStr = formatDate(today);

    for (let day = 1; day <= lastDay.getDate(); day++) {
      const currentDateStr = formatDate(new Date(year, month, day));
      const isPast =
        new Date(year, month, day) <
        new Date(today.getFullYear(), today.getMonth(), today.getDate());
      const isToday = currentDateStr === todayStr;
      const isSelected =
        selectedDate && currentDateStr === formatDate(selectedDate);

      let classes = "calendar-day";
      if (isPast) classes += " disabled";
      if (isToday) classes += " today";
      if (isSelected) classes += " selected";

      days += `<div class="${classes}" data-date="${currentDateStr}">${day}</div>`;
    }

    // Next month's days
    for (let j = 1; j <= nextDays; j++) {
      days += `<div class="calendar-day empty other-month">${j}</div>`;
    }

    calendarDays.innerHTML = days;

    // Add click events to calendar days
    document
      .querySelectorAll(".calendar-day:not(.disabled):not(.empty)")
      .forEach((day) => {
        day.addEventListener("click", function () {
          const dateStr = this.dataset.date;
          if (dateStr) {
            selectedDate = new Date(dateStr + "T00:00:00");
            dateHidden.value = dateStr;
            dateDisplay.value = formatDisplayDate(selectedDate);
            document.querySelectorAll(".calendar-day").forEach((d) => {
              d.classList.remove("selected");
            });
            this.classList.add("selected");
            dateCalendar.classList.remove("show");
          }
        });
      });
  }

  // Show/hide calendar on click
  dateDisplay.addEventListener("click", function (e) {
    e.stopPropagation();
    dateCalendar.classList.toggle("show");
    if (dateCalendar.classList.contains("show")) {
      generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    }
  });

  // Previous month button
  prevMonthBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
  });

  // Next month button
  nextMonthBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
  });

  // Close calendar when clicking outside
  document.addEventListener("click", function (e) {
    if (!dateDisplay.contains(e.target) && !dateCalendar.contains(e.target)) {
      dateCalendar.classList.remove("show");
    }
  });

  // Prevent calendar from closing when clicking inside
  dateCalendar.addEventListener("click", function (e) {
    e.stopPropagation();
  });

  // Function to clear all field errors
  function clearFieldErrors() {
    const errorDivs = document.querySelectorAll(".field-error");
    errorDivs.forEach((div) => {
      div.textContent = "";
    });
  }

  // Function to display field errors
  function displayFieldErrors(errors) {
    clearFieldErrors();
    for (const [field, message] of Object.entries(errors)) {
      let errorDiv = null;
      if (field === "date") {
        const datePickerContainer = document.querySelector(
          ".custom-date-picker"
        );
        if (datePickerContainer) {
          errorDiv = datePickerContainer.nextElementSibling;
        }
      } else if (field === "time") {
        const timePickerContainer = document.querySelector(
          ".custom-time-picker"
        );
        if (timePickerContainer) {
          errorDiv = timePickerContainer.nextElementSibling;
        }
      } else {
        const fieldMap = {
          name: "name",
          email: "email",
          mobile: "phone",
          guests: "guests",
          tableno: "table-no",
        };

        const formFieldName = fieldMap[field] || field;
        const inputElement = document.getElementById(formFieldName);
        if (inputElement) {
          errorDiv = inputElement.nextElementSibling;
        }
      }

      if (errorDiv && errorDiv.classList.contains("field-error")) {
        errorDiv.textContent = message;
      }
    }
  }

  // Load tables from JSON file
  function loadTables() {
    fetch("../config/tables.json")
      .then((response) => {
        if (!response.ok) {
          throw new Error("JSON file not found, trying API...");
        }
        return response.json();
      })
      .then((data) => {
        populateTableSelect(data.tables);
      })
      .catch((error) => {
        console.log("JSON file not accessible, trying PHP API...");
        fetch("../api/get_tables.php")
          .then((response) => response.json())
          .then((data) => {
            populateTableSelect(data.tables);
          })
          .catch((apiError) => {
            console.error("Error loading tables from API:", apiError);
            const defaultTables = [];
            for (let i = 1; i <= 20; i++) {
              defaultTables.push({
                id: i,
                name: `Table ${i}`,
                capacity: 4,
                location: "Main Area",
              });
            }
            populateTableSelect(defaultTables);
          });
      });
  }

  // Populate table select dropdown
  function populateTableSelect(tables) {
    tablesData = tables;
    tableSelect.innerHTML = '<option value="">Select a Table</option>';
    tables.forEach((table) => {
      const option = document.createElement("option");
      option.value = table.id;
      option.setAttribute("data-capacity", table.capacity);
      option.textContent = `${table.name} (${table.capacity} seats - ${table.location})`;
      tableSelect.appendChild(option);
    });

    console.log("Loaded", tables.length, "tables");
  }

  // Validate guest count against table capacity
  function validateCapacity() {
    const selectedTableId = parseInt(tableSelect.value);
    const guestCount = parseInt(document.getElementById("guests").value);

    if (!selectedTableId || !guestCount) {
      return true;
    }

    const selectedTable = tablesData.find(
      (table) => table.id === selectedTableId
    );

    if (selectedTable && guestCount > selectedTable.capacity) {
      alert(
        `Error: Selected table can accommodate only ${selectedTable.capacity} guests, but you entered ${guestCount} guests. Please select a larger table or reduce the number of guests.`
      );
      return false;
    }

    return true;
  }

  // Add real-time validation on guest count and table selection change
  document
    .getElementById("guests")
    .addEventListener("change", validateCapacity);
  tableSelect.addEventListener("change", validateCapacity);

  // Validate date and time against current date/time
  function validateDateTime() {
    const selectedDate = document.getElementById("date").value;
    const selectedTime = document.getElementById("time").value;

    if (!selectedDate || !selectedTime) {
      return true;
    }

    // Get current date and time
    const now = new Date();
    const currentDate = now.toISOString().split("T")[0]; // YYYY-MM-DD format
    const currentTime = now.toTimeString().split(" ")[0].substring(0, 5); // HH:MM format

    if (selectedDate < currentDate) {
      alert(
        "Error: You cannot book a table for a past date. Please select today's date or a future date."
      );
      return false;
    }

    // If booking for today, check if time is in the past
    if (selectedDate === currentDate && selectedTime <= currentTime) {
      alert(
        "Error: You cannot book a table for a past time. Please select a future time."
      );
      return false;
    }

    return true;
  }

  // Add real-time validation on date and time change
  document.getElementById("date").addEventListener("change", validateDateTime);
  document.getElementById("time").addEventListener("change", validateDateTime);

  // Validate table availability (1-hour booking slots)
  function validateTableAvailability() {
    const selectedTableId = document.getElementById("table-no").value;
    const selectedDate = document.getElementById("date").value;
    const selectedTime = document.getElementById("time").value;

    if (!selectedTableId || !selectedDate || !selectedTime) {
      return true;
    }

    // Calculate booking time range (1 hour slot)
    const bookingTime = new Date(`2000-01-01T${selectedTime}:00`);
    const bookingEnd = new Date(bookingTime.getTime() + 60 * 60 * 1000); // Add 1 hour
    const bookingStartStr = selectedTime;
    const bookingEndStr = bookingEnd.toTimeString().substring(0, 5);

    // Get current reservation ID if updating
    const currentReservationId = reservationIdInput.value;

    // Check existing reservations in the table
    const existingRows = document.querySelectorAll("#reservations-tbody tr");
    for (let row of existingRows) {
      const rowId = row.getAttribute("data-id");
      const rowDate = row.cells[4].textContent.trim();
      const rowTime = row.cells[5].textContent.trim();
      const rowTableNo = row.cells[7].textContent.trim();

      // Skip current reservation when updating
      if (currentReservationId && rowId === currentReservationId) {
        continue;
      }

      // Check if same table and date
      if (rowTableNo === selectedTableId && rowDate === selectedDate) {
        // Calculate existing booking time range (1 hour slot)
        const existingTime = new Date(`2000-01-01T${rowTime}:00`);
        const existingEnd = new Date(existingTime.getTime() + 60 * 60 * 1000);
        const existingStartStr = rowTime;
        const existingEndStr = existingEnd.toTimeString().substring(0, 5);

        // Check for time overlap
        const newStart = bookingTime.getTime();
        const newEnd = bookingEnd.getTime();
        const existingStart = existingTime.getTime();
        const existingEndTime = existingEnd.getTime();

        // Check if there's any overlap between the time slots
        if (
          (newStart >= existingStart && newStart < existingEndTime) ||
          (newEnd > existingStart && newEnd <= existingEndTime) ||
          (newStart <= existingStart && newEnd >= existingEndTime)
        ) {
          const customerName = row.cells[1].textContent.trim();
          alert(
            `Error: Table ${selectedTableId} is already reserved by ${customerName} from ${existingStartStr} to ${existingEndStr} on ${selectedDate}. Please select a different time.`
          );
          return false;
        }
      }
    }

    return true;
  }

  tableSelect.addEventListener("change", validateTableAvailability);
  document
    .getElementById("date")
    .addEventListener("change", validateTableAvailability);
  document
    .getElementById("time")
    .addEventListener("change", validateTableAvailability);

  function validateAll() {
    return (
      validateCapacity() && validateDateTime() && validateTableAvailability()
    );
  }
  loadTables();

  function updateButtonVisibility() {
    if (selectedRow) {
      bookBtn.style.display = "none";
      updateBtn.style.display = "inline-block";
    } else {
      bookBtn.style.display = "inline-block";
      updateBtn.style.display = "none";
    }
  }
  updateButtonVisibility();

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    if (!validateAll()) {
      return;
    }

    const formData = new FormData(form);

    bookBtn.disabled = true;
    bookBtn.textContent = "Booking...";

    fetch(window.location.href, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showMessage("Booking Successful!", "success");
          clearFieldErrors();
          resetForm();
          loadReservations();
        } else {
          if (data.errors) {
            displayFieldErrors(data.errors);
          } else {
            showMessage(
              "Error: " + (data.message || "An error occurred"),
              "error"
            );
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showMessage("Network error occurred. Please try again.", "error");
      })
      .finally(() => {
        bookBtn.disabled = false;
        bookBtn.textContent = "Book Now";
      });
  });

  updateBtn.addEventListener("click", function (e) {
    e.preventDefault();

    if (!selectedRow) {
      showMessage("Please select a reservation to update", "error");
      return;
    }
    if (!validateAll()) {
      return;
    }
    actionInput.value = "update";
    if (!reservationIdInput.value || reservationIdInput.value === "") {
      reservationIdInput.value = selectedRow.getAttribute("data-id");
    }
    const formData = new FormData();
    formData.append("action", "update");
    formData.append("id", reservationIdInput.value);
    formData.append("name", document.getElementById("name").value);
    formData.append("email", document.getElementById("email").value);
    formData.append("phone", document.getElementById("phone").value);
    formData.append("date", document.getElementById("date").value);
    formData.append("time", document.getElementById("time").value);
    formData.append("guests", document.getElementById("guests").value);
    formData.append("table-no", document.getElementById("table-no").value);
    console.log("Update data:", {
      action: "update",
      id: reservationIdInput.value,
      name: document.getElementById("name").value,
      email: document.getElementById("email").value,
    });
    updateBtn.disabled = true;
    updateBtn.textContent = "Updating...";
    fetch(window.location.href, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Server response:", data);
        if (data.success) {
          showMessage("Booking Details Updated Successfully!", "success");
          clearFieldErrors();
          resetForm();
          loadReservations();
        } else {
          if (data.errors) {
            displayFieldErrors(data.errors);
          } else {
            showMessage(
              "Error: " + (data.message || "An error occurred"),
              "error"
            );
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showMessage("Network error occurred. Please try again.", "error");
      })
      .finally(() => {
        updateBtn.disabled = false;
        updateBtn.textContent = "Update";
      });
  });

  cancelBtn.addEventListener("click", function () {
    resetForm();
  });

  // Reset form and selection
  function resetForm() {
    form.reset();
    actionInput.value = "book";
    reservationIdInput.value = "";
    selectedRow = null;
    originalData = null;
    clearFieldErrors();

    // Clear custom date picker
    dateDisplay.value = "";
    dateHidden.value = "";
    selectedDate = null;
    currentDate = new Date();
    document.querySelectorAll(".calendar-day").forEach((day) => {
      day.classList.remove("selected");
    });

    timeDisplay.value = "";
    timeHidden.value = "";
    document.querySelectorAll(".time-option").forEach((opt) => {
      opt.classList.remove("selected");
    });

    document.querySelectorAll("#reservations-tbody tr").forEach((row) => {
      row.classList.remove("selected");
    });

    updateButtonVisibility();

    console.log("Form reset");
  }

  // Load reservations from database
  function loadReservations() {
    fetch(window.location.href)
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newTbody = doc.getElementById("reservations-tbody");

        if (newTbody) {
          tableBody.innerHTML = newTbody.innerHTML;
          attachEventListeners();
        }
      })
      .catch((error) => {
        console.error("Error loading reservations:", error);
      });
  }

  function attachEventListeners() {
    document.querySelectorAll(".delete-btn").forEach((button) => {
      button.addEventListener("click", function (e) {
        e.stopPropagation();

        const id = this.getAttribute("data-id");
        if (confirm("Are you sure you want to delete this reservation?")) {
          deleteReservation(id);
        }
      });
    });

    document.querySelectorAll("#reservations-tbody tr").forEach((row) => {
      row.addEventListener("click", function (e) {
        // Don't select if delete button was clicked
        if (e.target.classList.contains("delete-btn")) {
          return;
        }

        console.log("Row clicked:", this); // Debug log
        selectRow(this);
      });
    });

    console.log(
      "Event listeners attached to",
      document.querySelectorAll("#reservations-tbody tr").length,
      "rows"
    ); // Debug log
  }

  function selectRow(row) {
    document.querySelectorAll("#reservations-tbody tr").forEach((r) => {
      r.classList.remove("selected");
    });

    row.classList.add("selected");
    selectedRow = row;
    const cells = row.cells;
    reservationIdInput.value = row.getAttribute("data-id");
    document.getElementById("name").value = cells[1].textContent.trim();
    document.getElementById("email").value = cells[2].textContent.trim();
    document.getElementById("phone").value = cells[3].textContent.trim();

    const dateStr = cells[4].textContent.trim();
    dateHidden.value = dateStr;
    selectedDate = new Date(dateStr + "T00:00:00");
    dateDisplay.value = formatDisplayDate(selectedDate);

    currentDate = new Date(selectedDate);
    const time24 = cells[5].textContent.trim();
    timeHidden.value = time24;

    // Convert to 12-hour format for display
    const [hours, minutes] = time24.split(":");
    const hour = parseInt(hours);
    const period = hour >= 12 ? "PM" : "AM";
    const hour12 = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    const time12 = `${hour12}:${minutes} ${period}`;
    timeDisplay.value = time12;

    document.querySelectorAll(".time-option").forEach((opt) => {
      opt.classList.remove("selected");
      if (opt.dataset.value === time24) {
        opt.classList.add("selected");
      }
    });

    document.getElementById("guests").value = cells[6].textContent.trim();
    document.getElementById("table-no").value = cells[7].textContent.trim();
    actionInput.value = "book";
    originalData = {
      id: row.getAttribute("data-id"),
      name: cells[1].textContent.trim(),
      email: cells[2].textContent.trim(),
      phone: cells[3].textContent.trim(),
      date: cells[4].textContent.trim(),
      time: cells[5].textContent.trim(),
      guests: cells[6].textContent.trim(),
      tableno: cells[7].textContent.trim(),
    };
    updateButtonVisibility();

    console.log("Row selected:", originalData); // Debug log
  }
  function deleteReservation(id) {
    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    fetch(window.location.href, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(data.message);
          resetForm();
          loadReservations();
        } else {
          alert(data.message || "An error occurred while deleting");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred while deleting the reservation");
      });
  }

  // Function to show messages to user (like orders-list.js)
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
  attachEventListeners();
});
