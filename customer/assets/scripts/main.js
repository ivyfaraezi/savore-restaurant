// Ensure order-sidebar is hidden on page load
document.addEventListener("DOMContentLoaded", function () {
  var orderSidebar = document.getElementById("order-sidebar");
  if (orderSidebar) {
    orderSidebar.style.display = "none";
  }
});
let menuItems = [];
// Format Price to Taka
function formatPriceToTaka(price) {
  const cleanPrice = price
    .toString()
    .replace(/[$৳Tk]/g, "")
    .trim();
  const numPrice = parseFloat(cleanPrice);
  if (isNaN(numPrice)) {
    return "৳0";
  }
  return `৳${Math.round(numPrice)}`;
}

// Parse Price value (remove currency symbols)
function parsePriceValue(price) {
  const cleanPrice = price
    .toString()
    .replace(/[$৳Tk]/g, "")
    .trim();
  const numPrice = parseFloat(cleanPrice);
  return isNaN(numPrice) ? 0 : numPrice;
}

// Load menu items from Database
function formatMenuTypeName(type) {
  let formatted = type.replace(/_/g, " ");
  formatted = formatted
    .split(" ")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(" ");

  return formatted;
}

// Load menu types for Dropdown
async function loadMenuTypes() {
  try {
    const response = await fetch("api/get_menu_types.php");
    const data = await response.json();

    if (data.success && data.data.length > 0) {
      const dropdown = document.getElementById("menu-type-filter");
      data.data.forEach((type) => {
        const option = document.createElement("option");
        option.value = type;
        option.textContent = formatMenuTypeName(type);
        dropdown.appendChild(option);
      });
    }
  } catch (error) {
    console.error("Error loading menu types:", error);
  }
}

async function loadMenuItems() {
  const menuContainer = document.getElementById("menu-items");
  const loadingElement = document.getElementById("menu-loading");
  const errorElement = document.getElementById("menu-error");

  try {
    loadingElement.style.display = "block";
    errorElement.style.display = "none";
    menuContainer.style.display = "none";

    const response = await fetch("api/get_menu.php");
    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message || "Failed to load menu items");
    }
    menuItems = data.data;
    renderMenuItems(data.data);
    loadingElement.style.display = "none";
    menuContainer.style.display = "grid";
  } catch (error) {
    console.error("Error loading menu:", error);
    loadingElement.style.display = "none";
    errorElement.style.display = "block";
  }
}

// Render Menu Items in the Menu Cards
function renderMenuItems(items) {
  const menuContainer = document.getElementById("menu-items");

  menuContainer.innerHTML = items
    .map(
      (item) => `
    <div class="menu-card">
      <img src="${item.photo}" alt="${item.name}" class="menu-card-img" />
      <h3>${item.name}</h3>
      <div class="menu-rating">★★★★★</div>
      <div class="menu-price">${formatPriceToTaka(item.price)}</div>
      <button class="add-cart-btn" data-item-id="${item.id}" data-item-name="${
        item.name
      }" data-item-price="${formatPriceToTaka(item.price)}">Add to Cart</button>
    </div>
  `
    )
    .join("");
  attachCartButtonListeners();
}

// Filter Menu Items by Type
function filterMenuByType(type) {
  if (type === "all") {
    renderMenuItems(menuItems);
  } else {
    const filteredItems = menuItems.filter((item) => item.type === type);
    renderMenuItems(filteredItems);
  }
}

// Add To Cart Tick and Confirmation
function attachCartButtonListeners() {
  const cartButtons = document.querySelectorAll(".add-cart-btn");
  cartButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const itemId = this.getAttribute("data-item-id");
      const itemName = this.getAttribute("data-item-name");
      const itemPrice = this.getAttribute("data-item-price");
      const item = menuItems.find((menuItem) => menuItem.id == itemId);
      if (item) {
        addToCart({
          id: itemId,
          name: itemName,
          price: itemPrice,
          image: item.photo,
        });
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fa fa-check"></i>';
        this.style.background = "#4CAF50";

        setTimeout(() => {
          this.innerHTML = originalText;
          this.style.background = "#bfa46b";
        }, 1000);
      }
    });
  });
}

// Check if user is already logged in and load their data
function initializeUserSession() {
  const userIcon = document.querySelector(".user-icon");
  const floatingOrdersBtn = document.getElementById("floating-orders-btn");
  const floatingReservationsBtn = document.getElementById(
    "floating-reservations-btn"
  );

  if (userIcon && userIcon.style.display !== "none") {
    console.log("User session detected, loading user data...");
    if (typeof loadUserOrders === "function") {
      loadUserOrders();
    }
    if (typeof loadUserReservations === "function") {
      loadUserReservations();
    }
  }
}

// Load menu items when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  loadMenuTypes();
  loadMenuItems();
  initializeUserSession();
  const retryBtn = document.getElementById("retry-menu-btn");
  if (retryBtn) {
    retryBtn.addEventListener("click", loadMenuItems);
  }
  const menuTypeFilter = document.getElementById("menu-type-filter");
  if (menuTypeFilter) {
    menuTypeFilter.addEventListener("change", function () {
      filterMenuByType(this.value);
    });
  }
});

const heroImages = [
  "assets/image/hero1.png",
  "assets/image/hero2.jpg",
  "assets/image/hero3.png",
];
let currentHero = 0;
const heroImgElement = document.querySelector(".hero-img");
if (heroImgElement) {
  setInterval(() => {
    currentHero = (currentHero + 1) % heroImages.length;
    heroImgElement.src = heroImages[currentHero];
  }, 2000);
}

document.querySelector(".signin-btn").addEventListener("click", function () {
  document.getElementById("signin-modal").style.display = "flex";
});
document.getElementById("close-modal").addEventListener("click", function () {
  document.getElementById("signin-modal").style.display = "none";
});
document.getElementById("signin-modal").addEventListener("click", function (e) {
  if (e.target === this) {
    this.style.display = "none";
  }
});
document.getElementById("signin-form").addEventListener("submit", function (e) {
  e.preventDefault();
  document.getElementById("signin-email-error").textContent = "";
  document.getElementById("signin-password-error").textContent = "";
  const signinMessage = document.getElementById("signin-message");
  signinMessage.textContent = "";
  signinMessage.className = "message-container";

  const email = document.getElementById("signin-email").value.trim();
  const password = document.getElementById("signin-password").value;
  const rememberMe = document.getElementById("remember-me").checked;

  const submitBtn = document.getElementById("signin-submit-btn");
  const btnText = submitBtn ? submitBtn.querySelector(".btn-text") : null;
  const btnSpinner = submitBtn ? submitBtn.querySelector(".btn-spinner") : null;

  // show spinner & disable button
  if (submitBtn) {
    submitBtn.disabled = true;
  }
  if (btnText) {
    btnText.style.display = "none";
  }
  if (btnSpinner) {
    btnSpinner.style.display = "inline-block";
  }

  const formData = new FormData();
  formData.append("email", email);
  formData.append("password", password);
  if (rememberMe) {
    formData.append("remember_me", "on");
  }

  // allow DOM updates (spinner) to paint before starting the network request
  setTimeout(() => {
    fetch("auth/login.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          return response.text().then((text) => {
            throw new Error("HTTP " + response.status + " - " + text);
          });
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          document.querySelector(".signin-btn").style.display = "none";
          document.querySelector(".user-icon").style.display = "inline-block";
          document.getElementById("signin-modal").style.display = "none";
          // If server requests a redirect (for admin/employee), follow it
          if (data.redirect) {
            window.location.href = data.redirect;
            return;
          }
          const customer = data.customer;
          document.getElementById("dropdown-username").textContent =
            customer.name;
          document.getElementById("dropdown-name").textContent = customer.name;
          document.getElementById("dropdown-email").textContent =
            customer.email;
          document.getElementById("dropdown-mobile").textContent =
            customer.mobile;
          const floatingBtn = document.getElementById("floating-orders-btn");
          if (floatingBtn) {
            floatingBtn.style.display = "flex";
          }

          const floatingReservationBtn = document.getElementById(
            "floating-reservations-btn"
          );
          if (floatingReservationBtn) {
            floatingReservationBtn.style.display = "flex";
          }
          this.reset();
        } else {
          if (data.errors) {
            if (data.errors.email) {
              document.getElementById("signin-email-error").textContent =
                data.errors.email;
            }
            if (data.errors.password) {
              document.getElementById("signin-password-error").textContent =
                data.errors.password;
            }
            signinMessage.textContent =
              data.message || "Please fix the errors.";
            signinMessage.className = "message-container error";
          } else {
            signinMessage.textContent = data.message || "Login failed.";
            signinMessage.className = "message-container error";
          }
        }
      })
      .catch((error) => {
        console.error("Error during login fetch:", error);
        signinMessage.textContent =
          error.message || "Network error. Please try again.";
        signinMessage.className = "message-container error";
      })
      .finally(() => {
        // restore button state
        if (submitBtn) {
          submitBtn.disabled = false;
        }
        if (btnText) {
          btnText.style.display = "";
        }
        if (btnSpinner) {
          btnSpinner.style.display = "none";
        }
      });
  }, 50);
});

// Password Toggle Functionality for Login
const toggleSigninPassword = document.getElementById("toggle-signin-password");
if (toggleSigninPassword) {
  toggleSigninPassword.addEventListener("click", function () {
    const passwordInput = document.getElementById("signin-password");
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    if (type === "text") {
      this.classList.remove("fa-eye-slash");
      this.classList.add("fa-eye");
    } else {
      this.classList.remove("fa-eye");
      this.classList.add("fa-eye-slash");
    }
  });
}

// Password Toggle Functionality for Signup
const toggleSignupPassword = document.getElementById("toggle-signup-password");
if (toggleSignupPassword) {
  toggleSignupPassword.addEventListener("click", function () {
    const passwordInput = document.getElementById("signup-password");
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    if (type === "text") {
      this.classList.remove("fa-eye-slash");
      this.classList.add("fa-eye");
    } else {
      this.classList.remove("fa-eye");
      this.classList.add("fa-eye-slash");
    }
  });
}

// Password Toggle Functionality for Signup Confirm Password
const toggleSignupConfirmPassword = document.getElementById(
  "toggle-signup-confirm-password"
);
if (toggleSignupConfirmPassword) {
  toggleSignupConfirmPassword.addEventListener("click", function () {
    const passwordInput = document.getElementById("signup-confirm-password");
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);

    // Toggle icon
    if (type === "text") {
      this.classList.remove("fa-eye-slash");
      this.classList.add("fa-eye");
    } else {
      this.classList.remove("fa-eye");
      this.classList.add("fa-eye-slash");
    }
  });
}

const userIcon = document.querySelector(".user-icon");
const userDropdown = document.querySelector(".user-dropdown");
userIcon.addEventListener("click", function (e) {
  e.stopPropagation();
  userDropdown.style.display =
    userDropdown.style.display === "block" ? "none" : "block";
});
document.addEventListener("click", function (e) {
  if (userDropdown.style.display === "block") {
    userDropdown.style.display = "none";
  }
});
document.getElementById("logout-btn").addEventListener("click", function () {
  if (!confirm("Are you want to log out ?")) return;
  fetch("auth/logout.php", {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        userIcon.style.display = "none";
        document.querySelector(".signin-btn").style.display = "inline-block";
        document.getElementById("dropdown-username").textContent = "";
        document.getElementById("dropdown-name").textContent = "";
        document.getElementById("dropdown-email").textContent = "";
        document.getElementById("dropdown-mobile").textContent = "";
        hideOrderSidebar();
        hideReservationSidebar();
        const floatingBtn = document.getElementById("floating-orders-btn");
        if (floatingBtn) {
          floatingBtn.style.display = "none";
        }
        const floatingReservationBtn = document.getElementById(
          "floating-reservations-btn"
        );
        if (floatingReservationBtn) {
          floatingReservationBtn.style.display = "none";
        }
        window.location.href = "index.php";
      }
    })
    .catch((error) => {
      console.error("Logout error:", error);
      window.location.href = "index.php";
    });
});

document
  .getElementById("change-password-btn")
  .addEventListener("click", function () {
    document.getElementById("change-password-modal").style.display = "flex";
    document.querySelector(".user-dropdown").style.display = "none";
  });

document
  .getElementById("close-change-password-modal")
  .addEventListener("click", function () {
    document.getElementById("change-password-modal").style.display = "none";
    clearChangePasswordForm();
  });

document
  .getElementById("change-password-modal")
  .addEventListener("click", function (e) {
    if (e.target === this) {
      this.style.display = "none";
      clearChangePasswordForm();
    }
  });

function clearChangePasswordForm() {
  document.getElementById("change-password-form").reset();
  const messageContainer = document.getElementById("change-password-message");
  messageContainer.textContent = "";
  messageContainer.className = "message-container";
}

function showChangePasswordMessage(message, type) {
  const messageContainer = document.getElementById("change-password-message");
  messageContainer.textContent = message;
  messageContainer.className = "message-container " + type;
}

document
  .getElementById("change-password-form")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const currentPassword = document
      .getElementById("current-password")
      .value.trim();
    const newPassword = document.getElementById("new-password").value.trim();
    const confirmNewPassword = document
      .getElementById("confirm-new-password")
      .value.trim();
    if (!currentPassword || !newPassword || !confirmNewPassword) {
      showChangePasswordMessage("All fields are required", "error");
      return;
    }

    if (newPassword !== confirmNewPassword) {
      showChangePasswordMessage(
        "New password and confirm password do not match",
        "error"
      );
      return;
    }

    if (newPassword.length < 6) {
      showChangePasswordMessage(
        "New password must be at least 6 characters long",
        "error"
      );
      return;
    }

    if (!/^(?=.*[A-Za-z])(?=.*\d)/.test(newPassword)) {
      showChangePasswordMessage(
        "New password must contain at least one letter and one number",
        "error"
      );
      return;
    }

    if (currentPassword === newPassword) {
      showChangePasswordMessage(
        "New password must be different from your current password",
        "error"
      );
      return;
    }

    const formData = new FormData();
    formData.append("current_password", currentPassword);
    formData.append("new_password", newPassword);
    formData.append("confirm_new_password", confirmNewPassword);

    fetch("auth/change_password.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showChangePasswordMessage(data.message, "success");
          setTimeout(() => {
            document.getElementById("change-password-modal").style.display =
              "none";
            clearChangePasswordForm();
          }, 2000);
        } else {
          showChangePasswordMessage(data.message, "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showChangePasswordMessage(
          "An error occurred. Please try again.",
          "error"
        );
      });
  });

document.querySelectorAll(".edit-icon").forEach(function (icon) {
  icon.addEventListener("click", function (e) {
    e.stopPropagation();
    var field = icon.getAttribute("data-edit");
    var span = document.getElementById("dropdown-" + field);
    var currentValue = span.textContent;
    var input = document.createElement("input");
    input.type = "text";
    input.value = currentValue;
    input.style.width = "70%";
    input.style.marginRight = "8px";
    span.style.display = "none";
    icon.style.display = "none";
    span.parentNode.insertBefore(input, span);
    input.focus();
    input.addEventListener("blur", function () {
      span.textContent = input.value;
      span.style.display = "";
      icon.style.display = "";
      input.remove();
    });
    input.addEventListener("keydown", function (ev) {
      if (ev.key === "Enter") {
        input.blur();
      }
    });
  });
});

// Custom Date Picker Implementation
class CustomDatePicker {
  constructor(
    displayInputId,
    hiddenInputId,
    calendarId,
    prevBtnId,
    nextBtnId,
    monthYearId,
    daysContainerId
  ) {
    this.displayInput = document.getElementById(displayInputId);
    this.hiddenInput = document.getElementById(hiddenInputId);
    this.calendar = document.getElementById(calendarId);
    this.prevBtn = document.getElementById(prevBtnId);
    this.nextBtn = document.getElementById(nextBtnId);
    this.monthYearSpan = document.getElementById(monthYearId);
    this.daysContainer = document.getElementById(daysContainerId);

    this.currentDate = new Date();
    this.selectedDate = null;
    this.minDate = new Date();

    this.init();
  }

  init() {
    if (!this.displayInput) return;

    this.displayInput.addEventListener("click", (e) => {
      e.stopPropagation();
      this.calendar.classList.toggle("active");
    });

    this.prevBtn.addEventListener("click", () => this.previousMonth());
    this.nextBtn.addEventListener("click", () => this.nextMonth());

    document.addEventListener("click", (e) => {
      if (!this.calendar.contains(e.target) && e.target !== this.displayInput) {
        this.calendar.classList.remove("active");
      }
    });

    this.renderCalendar();
  }

  renderCalendar() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    this.monthYearSpan.textContent = new Date(year, month).toLocaleDateString(
      "en-US",
      {
        month: "long",
        year: "numeric",
      }
    );

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    this.daysContainer.innerHTML = "";
    for (let i = firstDay - 1; i >= 0; i--) {
      const dayDiv = document.createElement("div");
      dayDiv.className = "calendar-day other-month";
      dayDiv.textContent = daysInPrevMonth - i;
      this.daysContainer.appendChild(dayDiv);
    }
    for (let day = 1; day <= daysInMonth; day++) {
      const dayDiv = document.createElement("div");
      dayDiv.className = "calendar-day";
      dayDiv.textContent = day;

      const currentDateCheck = new Date(year, month, day);
      if (currentDateCheck < this.minDate.setHours(0, 0, 0, 0)) {
        dayDiv.classList.add("disabled");
      } else {
        dayDiv.addEventListener("click", () => this.selectDate(day));
      }
      const today = new Date();
      if (
        day === today.getDate() &&
        month === today.getMonth() &&
        year === today.getFullYear()
      ) {
        dayDiv.classList.add("today");
      }
      if (
        this.selectedDate &&
        day === this.selectedDate.getDate() &&
        month === this.selectedDate.getMonth() &&
        year === this.selectedDate.getFullYear()
      ) {
        dayDiv.classList.add("selected");
      }

      this.daysContainer.appendChild(dayDiv);
    }
    const totalCells = this.daysContainer.children.length;
    const remainingCells = 42 - totalCells;
    for (let i = 1; i <= remainingCells; i++) {
      const dayDiv = document.createElement("div");
      dayDiv.className = "calendar-day other-month";
      dayDiv.textContent = i;
      this.daysContainer.appendChild(dayDiv);
    }
  }

  selectDate(day) {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();
    this.selectedDate = new Date(year, month, day);

    const formattedDate = this.selectedDate.toLocaleDateString("en-US", {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
    });

    const isoDate = this.selectedDate.toISOString().split("T")[0];

    this.displayInput.value = formattedDate;
    this.hiddenInput.value = isoDate;
    this.calendar.classList.remove("active");
    this.renderCalendar();
  }

  previousMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
    this.renderCalendar();
  }

  nextMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
    this.renderCalendar();
  }
}

// Custom Time Picker Implementation
class CustomTimePicker {
  constructor(displayInputId, hiddenInputId, dropdownId, listId) {
    this.displayInput = document.getElementById(displayInputId);
    this.hiddenInput = document.getElementById(hiddenInputId);
    this.dropdown = document.getElementById(dropdownId);
    this.list = document.getElementById(listId);

    this.selectedTime = null;

    this.init();
  }

  init() {
    if (!this.displayInput) return;

    this.displayInput.addEventListener("click", (e) => {
      e.stopPropagation();
      this.dropdown.classList.toggle("active");
    });

    document.addEventListener("click", (e) => {
      if (!this.dropdown.contains(e.target) && e.target !== this.displayInput) {
        this.dropdown.classList.remove("active");
      }
    });

    this.generateTimeSlots();
  }

  generateTimeSlots() {
    this.list.innerHTML = "";
    for (let hour = 9; hour <= 22; hour++) {
      for (let minute = 0; minute < 60; minute += 30) {
        if (hour === 22 && minute > 0) break;

        const timeValue = `${String(hour).padStart(2, "0")}:${String(
          minute
        ).padStart(2, "0")}`;
        const displayTime = this.formatTime(hour, minute);

        const timeSlot = document.createElement("div");
        timeSlot.className = "time-slot";
        timeSlot.textContent = displayTime;
        timeSlot.dataset.time = timeValue;

        timeSlot.addEventListener("click", () =>
          this.selectTime(timeValue, displayTime)
        );

        this.list.appendChild(timeSlot);
      }
    }
  }

  formatTime(hour, minute) {
    const period = hour >= 12 ? "PM" : "AM";
    const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    return `${displayHour}:${String(minute).padStart(2, "0")} ${period}`;
  }

  selectTime(timeValue, displayTime) {
    this.selectedTime = timeValue;
    this.displayInput.value = displayTime;
    this.hiddenInput.value = timeValue;
    this.dropdown.classList.remove("active");
    this.list.querySelectorAll(".time-slot").forEach((slot) => {
      slot.classList.remove("selected");
      if (slot.dataset.time === timeValue) {
        slot.classList.add("selected");
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const reserveModal = document.getElementById("reserve-modal");
  const reserveBtn = document.querySelector(".reserve-btn");
  const closeReserveBtn = document.getElementById("close-reserve-modal");
  const reserveForm = document.getElementById("reserve-form");
  const reserveMessage = document.getElementById("reserve-message");
  const reserveDatePicker = new CustomDatePicker(
    "reserve-date-display",
    "reserve-date",
    "reserve-date-picker",
    "reserve-prev-month",
    "reserve-next-month",
    "reserve-calendar-month-year",
    "reserve-calendar-days"
  );

  const reserveTimePicker = new CustomTimePicker(
    "reserve-time-display",
    "reserve-time",
    "reserve-time-picker",
    "reserve-time-list"
  );

  if (reserveBtn) {
    reserveBtn.onclick = () => {
      if (!isUserLoggedIn()) {
        const signinModal = document.getElementById("signin-modal");
        if (signinModal) {
          signinModal.style.display = "flex";
        }
        showMessage("Please login to reserve a table", "error");
        return;
      }

      reserveModal.style.display = "flex";
      reserveMessage.textContent = "";
      reserveMessage.style.display = "none";
      reserveForm.reset();
      const userName = document.getElementById("dropdown-name").textContent;
      const userEmail = document.getElementById("dropdown-email").textContent;
      const userMobile = document.getElementById("dropdown-mobile").textContent;

      document.getElementById("reserve-name").value = userName;
      document.getElementById("reserve-email").value = userEmail;
      document.getElementById("reserve-phone").value = userMobile;

      document.getElementById("reserve-date-display").value = "";
      document.getElementById("reserve-date").value = "";
      document.getElementById("reserve-time-display").value = "";
      document.getElementById("reserve-time").value = "";
    };
  }

  if (closeReserveBtn) {
    closeReserveBtn.onclick = () => {
      reserveModal.style.display = "none";
    };
  }

  window.onclick = (e) => {
    if (e.target == reserveModal) reserveModal.style.display = "none";
  };

  if (reserveForm) {
    reserveForm.onsubmit = async (e) => {
      e.preventDefault();
      document.getElementById("reserve-name-error").textContent = "";
      document.getElementById("reserve-email-error").textContent = "";
      document.getElementById("reserve-phone-error").textContent = "";
      document.getElementById("reserve-date-error").textContent = "";
      document.getElementById("reserve-time-error").textContent = "";
      document.getElementById("reserve-guests-error").textContent = "";
      reserveMessage.style.display = "none";
      reserveMessage.textContent = "";
      // Check if user is logged in before submitting
      if (!isUserLoggedIn()) {
        reserveMessage.style.display = "block";
        reserveMessage.style.color = "red";
        reserveMessage.textContent = "Please login to reserve a table.";
        setTimeout(() => {
          reserveModal.style.display = "none";
          const signinModal = document.getElementById("signin-modal");
          if (signinModal) {
            signinModal.style.display = "flex";
          }
        }, 1500);
        return;
      }
      const name = document.getElementById("reserve-name").value.trim();
      const email = document.getElementById("reserve-email").value.trim();
      const phone = document.getElementById("reserve-phone").value.trim();
      const date = document.getElementById("reserve-date").value;
      const time = document.getElementById("reserve-time").value;
      const guests = document.getElementById("reserve-guests").value;
      const submitBtn = reserveForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Reserving...";

      try {
        const formData = new FormData();
        formData.append("name", name);
        formData.append("email", email);
        formData.append("mobile", phone);
        formData.append("date", date);
        formData.append("time", time);
        formData.append("guests", guests);

        const response = await fetch("api/reserve_table.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          reserveMessage.style.display = "block";
          reserveMessage.style.color = "green";
          reserveMessage.textContent = data.message;
          reserveForm.reset();
          document.getElementById("reserve-date-display").value = "";
          document.getElementById("reserve-time-display").value = "";

          setTimeout(() => {
            reserveModal.style.display = "none";
            reserveMessage.style.display = "none";
          }, 3000);
        } else {
          if (data.errors) {
            if (data.errors.name) {
              document.getElementById("reserve-name-error").textContent =
                data.errors.name;
            }
            if (data.errors.email) {
              document.getElementById("reserve-email-error").textContent =
                data.errors.email;
            }
            if (data.errors.mobile) {
              document.getElementById("reserve-phone-error").textContent =
                data.errors.mobile;
            }
            if (data.errors.date) {
              document.getElementById("reserve-date-error").textContent =
                data.errors.date;
            }
            if (data.errors.time) {
              document.getElementById("reserve-time-error").textContent =
                data.errors.time;
            }
            if (data.errors.guests) {
              document.getElementById("reserve-guests-error").textContent =
                data.errors.guests;
            }
          }
          reserveMessage.style.display = "block";
          reserveMessage.style.color = "red";
          reserveMessage.textContent =
            data.message || "Please fix the errors below.";
          if (data.require_login) {
            setTimeout(() => {
              reserveModal.style.display = "none";
              const signinModal = document.getElementById("signin-modal");
              if (signinModal) {
                signinModal.style.display = "flex";
              }
            }, 1500);
          }
        }
      } catch (error) {
        console.error("Reservation error:", error);
        reserveMessage.style.display = "block";
        reserveMessage.style.color = "red";
        reserveMessage.textContent = "An error occurred. Please try again.";
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    };
  }
});

document
  .querySelectorAll(".star-rating .star")
  .forEach(function (star, idx, stars) {
    star.addEventListener("click", function () {
      const value = parseInt(star.getAttribute("data-value"));
      stars.forEach(function (s, i) {
        const icon = s.querySelector("i");
        if (i < value) {
          icon.classList.remove("fa-regular");
          icon.classList.add("fa-solid");
        } else {
          icon.classList.remove("fa-solid");
          icon.classList.add("fa-regular");
        }
      });
      document.getElementById("review-stars").value = value;
    });
  });

// Sign Up Modal Functionality
document.getElementById("sign-up-link").addEventListener("click", function (e) {
  e.preventDefault();
  document.getElementById("signin-modal").style.display = "none";
  document.getElementById("signup-modal").style.display = "flex";
});

document
  .getElementById("close-signup-modal")
  .addEventListener("click", function () {
    document.getElementById("signup-modal").style.display = "none";
  });

document.getElementById("signup-modal").addEventListener("click", function (e) {
  if (e.target === this) {
    this.style.display = "none";
  }
});

// Forgot Password Modal Functionality
function initializeForgotPassword() {
  const forgotPasswordLink = document.getElementById("forgot-password-link");
  if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener("click", function (e) {
      e.preventDefault();
      document.getElementById("signin-modal").style.display = "none";
      document.getElementById("forgot-password-modal").style.display = "flex";
    });
  }
  const closeForgotPasswordModal = document.getElementById(
    "close-forgot-password-modal"
  );
  if (closeForgotPasswordModal) {
    closeForgotPasswordModal.addEventListener("click", function () {
      document.getElementById("forgot-password-modal").style.display = "none";
      clearForgotPasswordForm();
    });
  }
  const forgotPasswordModal = document.getElementById("forgot-password-modal");
  if (forgotPasswordModal) {
    forgotPasswordModal.addEventListener("click", function (e) {
      if (e.target === this) {
        this.style.display = "none";
        clearForgotPasswordForm();
      }
    });
  }

  // Back to Sign In Link
  const backToSigninLink = document.getElementById("back-to-signin-link");
  if (backToSigninLink) {
    backToSigninLink.addEventListener("click", function (e) {
      e.preventDefault();
      document.getElementById("forgot-password-modal").style.display = "none";
      document.getElementById("signin-modal").style.display = "flex";
      clearForgotPasswordForm();
    });
  }

  // Forgot Password Form Submission
  const forgotPasswordForm = document.getElementById("forgot-password-form");
  if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", function (e) {
      e.preventDefault();
      document.getElementById("forgot-password-email-error").textContent = "";
      const messageDiv = document.getElementById("forgot-password-message");
      messageDiv.textContent = "";
      messageDiv.className = "message-container";

      const email = document
        .getElementById("forgot-password-email")
        .value.trim();
      const submitBtn = document.querySelector(
        "#forgot-password-form button[type='submit']"
      );
      const originalText = submitBtn.textContent;
      submitBtn.textContent = "Sending...";
      submitBtn.disabled = true;

      const formData = new FormData();
      formData.append("email", email);

      fetch("auth/forgot_password.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text().then((text) => {
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error("Invalid JSON response:", text);
              throw new Error("Server returned invalid response");
            }
          });
        })
        .then((data) => {
          if (data.success) {
            showForgotPasswordMessage(data.message, "success");
            document.getElementById("forgot-password-form").reset();
          } else {
            if (data.errors) {
              if (data.errors.email) {
                document.getElementById(
                  "forgot-password-email-error"
                ).textContent = data.errors.email;
              }
            }
            showForgotPasswordMessage(
              data.message || "Please fix the errors below.",
              "error"
            );
          }
        })
        .catch((error) => {
          console.error("Forgot password error:", error);
          showForgotPasswordMessage(
            `Error: ${error.message}. Please check console for details.`,
            "error"
          );
        })
        .finally(() => {
          submitBtn.textContent = originalText;
          submitBtn.disabled = false;
        });
    });
  }
}

// Helper functions for forgot password
function clearForgotPasswordForm() {
  const form = document.getElementById("forgot-password-form");
  const messageDiv = document.getElementById("forgot-password-message");
  const emailError = document.getElementById("forgot-password-email-error");
  if (form) form.reset();
  if (messageDiv) {
    messageDiv.textContent = "";
    messageDiv.className = "message-container";
  }
  if (emailError) {
    emailError.textContent = "";
  }
}

function showForgotPasswordMessage(message, type) {
  const messageDiv = document.getElementById("forgot-password-message");
  if (messageDiv) {
    messageDiv.textContent = message;
    messageDiv.className = `message-container ${type}`;
  }
}
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeForgotPassword);
} else {
  initializeForgotPassword();
}

// Enhanced signup form handling with OTP verification
document.getElementById("signup-form").addEventListener("submit", function (e) {
  e.preventDefault();
  document.getElementById("signup-name-error").textContent = "";
  document.getElementById("signup-email-error").textContent = "";
  document.getElementById("signup-mobile-error").textContent = "";
  document.getElementById("signup-password-error").textContent = "";
  document.getElementById("signup-cpassword-error").textContent = "";

  const signupMessage = document.getElementById("signup-message");
  signupMessage.textContent = "";
  signupMessage.className = "message-container";

  const name = document.getElementById("signup-name").value.trim();
  const email = document.getElementById("signup-email").value.trim();
  const mobile = document.getElementById("signup-mobile").value.trim();
  const password = document.getElementById("signup-password").value;
  const confirmPassword = document.getElementById(
    "signup-confirm-password"
  ).value;
  showLoading(true);
  const formData = new FormData();
  formData.append("name", name);
  formData.append("email", email);
  formData.append("mobile", mobile);
  formData.append("password", password);
  formData.append("cpassword", confirmPassword);

  fetch("auth/send_otp.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      showLoading(false);

      if (data.success) {
        window.currentSignupEmail = email;
        window.currentSignupName = name;
        showSignupStep(2);
        document.getElementById("verification-email").textContent = email;
        showMessage(data.message, "success");
        startOtpTimer();
      } else {
        if (data.errors) {
          if (data.errors.name) {
            document.getElementById("signup-name-error").textContent =
              data.errors.name;
          }
          if (data.errors.email) {
            document.getElementById("signup-email-error").textContent =
              data.errors.email;
          }
          if (data.errors.mobile) {
            document.getElementById("signup-mobile-error").textContent =
              data.errors.mobile;
          }
          if (data.errors.password) {
            document.getElementById("signup-password-error").textContent =
              data.errors.password;
          }
          if (data.errors.cpassword) {
            document.getElementById("signup-cpassword-error").textContent =
              data.errors.cpassword;
          }
        }
        showMessage(data.message || "Please fix the errors below.", "error");
      }
    })
    .catch((error) => {
      showLoading(false);
      console.error("Error:", error);
      showMessage("Network error. Please try again.", "error");
    });
});

// OTP Form handling
document.getElementById("otp-form").addEventListener("submit", function (e) {
  e.preventDefault();

  const otp = document.getElementById("otp-input").value.trim();
  const email = window.currentSignupEmail;

  if (!otp || otp.length !== 6) {
    showMessage("Please enter a valid 6-digit OTP", "error");
    return;
  }
  showLoading(true);

  const formData = new FormData();
  formData.append("email", email);
  formData.append("otp", otp);

  fetch("auth/verify_otp.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      showLoading(false);

      if (data.success) {
        showSignupStep(3);
        document.getElementById("welcome-name").textContent =
          data.customer_name || window.currentSignupName;
        showMessage(data.message, "success");
        clearOtpTimer();
      } else {
        showMessage(data.message, "error");
      }
    })
    .catch((error) => {
      showLoading(false);
      console.error("Error:", error);
      showMessage("Network error. Please try again.", "error");
    });
});

// Resend OTP functionality
document
  .getElementById("resend-otp-btn")
  .addEventListener("click", function () {
    if (this.disabled) return;

    const email = window.currentSignupEmail;
    const name = window.currentSignupName;

    showLoading(true);

    const formData = new FormData();
    formData.append("name", name);
    formData.append("email", email);
    formData.append("mobile", document.getElementById("signup-mobile").value);
    formData.append(
      "password",
      document.getElementById("signup-password").value
    );
    formData.append(
      "cpassword",
      document.getElementById("signup-confirm-password").value
    );

    fetch("auth/send_otp.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        showLoading(false);

        if (data.success) {
          showMessage("New OTP sent to your email", "success");
          document.getElementById("otp-input").value = "";
          startOtpTimer();
        } else {
          showMessage(data.message, "error");
        }
      })
      .catch((error) => {
        showLoading(false);
        console.error("Error:", error);
        showMessage("Network error. Please try again.", "error");
      });
  });

// Change email functionality
document
  .getElementById("change-email-btn")
  .addEventListener("click", function () {
    showSignupStep(1);
    clearOtpTimer();
    showMessage("Please enter a different email address", "info");
  });

// Go to signin functionality
document
  .getElementById("go-to-signin-btn")
  .addEventListener("click", function () {
    document.getElementById("signup-modal").style.display = "none";
    document.getElementById("signin-modal").style.display = "flex";
    resetSignupModal();
  });

// Sign In Link Functionality (from signup modal back to signin modal)
document.getElementById("sign-in-link").addEventListener("click", function (e) {
  e.preventDefault();
  document.getElementById("signup-modal").style.display = "none";
  document.getElementById("signin-modal").style.display = "flex";
  resetSignupModal();
});

// Helper functions for signup process
function showSignupStep(step) {
  document.querySelectorAll(".signup-step").forEach((el) => {
    el.style.display = "none";
  });
  const stepElement = document.querySelector(`[data-step="${step}"]`);
  if (stepElement) {
    stepElement.style.display = "block";
  }
  const title = document.getElementById("signup-modal-title");
  switch (step) {
    case 1:
      title.textContent = "Sign Up";
      break;
    case 2:
      title.textContent = "Verify Email";
      break;
    case 3:
      title.textContent = "Welcome!";
      break;
  }
}

function showMessage(message, type) {
  const messageContainer = document.getElementById("signup-message");
  messageContainer.textContent = message;
  messageContainer.className = `message-container ${type}`;
  messageContainer.style.display = "block";
  if (type === "success" || type === "info") {
    setTimeout(() => {
      messageContainer.style.display = "none";
    }, 5000);
  }
}

function showLoading(show) {
  const loadingIndicator = document.getElementById("loading-indicator");
  loadingIndicator.style.display = show ? "block" : "none";
}

let otpTimer;
let otpTimeLeft = 600;

function startOtpTimer() {
  clearOtpTimer();
  otpTimeLeft = 600;

  const resendBtn = document.getElementById("resend-otp-btn");
  const timerDiv = document.getElementById("otp-timer");

  resendBtn.disabled = true;
  resendBtn.textContent = "Resend Code";

  otpTimer = setInterval(() => {
    otpTimeLeft--;

    const minutes = Math.floor(otpTimeLeft / 60);
    const seconds = otpTimeLeft % 60;

    if (otpTimeLeft > 0) {
      timerDiv.textContent = `OTP expires in ${minutes}:${seconds
        .toString()
        .padStart(2, "0")}`;
      if (otpTimeLeft <= 540) {
        resendBtn.disabled = false;
      }
    } else {
      timerDiv.textContent = "OTP has expired. Please request a new one.";
      resendBtn.disabled = false;
      clearOtpTimer();
    }
  }, 1000);
}

function clearOtpTimer() {
  if (otpTimer) {
    clearInterval(otpTimer);
    otpTimer = null;
  }

  const timerDiv = document.getElementById("otp-timer");
  const resendBtn = document.getElementById("resend-otp-btn");

  timerDiv.textContent = "";
  resendBtn.disabled = false;
}

function resetSignupModal() {
  showSignupStep(1);
  document.getElementById("signup-form").reset();
  document.getElementById("otp-form").reset();
  document.getElementById("signup-message").style.display = "none";
  clearOtpTimer();
  window.currentSignupEmail = null;
  window.currentSignupName = null;
  showLoading(false);
}

document
  .getElementById("close-signup-modal")
  .addEventListener("click", function () {
    document.getElementById("signup-modal").style.display = "none";
    resetSignupModal();
  });

document.getElementById("signup-modal").addEventListener("click", function (e) {
  if (e.target === this) {
    this.style.display = "none";
    resetSignupModal();
  }
});

// Search functionality
document.addEventListener("DOMContentLoaded", function () {
  const searchBar = document.querySelector(".search-bar");
  const searchDropdown = document.getElementById("search-dropdown");
  searchBar.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    if (searchTerm.length === 0) {
      searchDropdown.style.display = "none";
      return;
    }
    const filteredItems = menuItems.filter((item) =>
      item.name.toLowerCase().includes(searchTerm)
    );
    searchDropdown.innerHTML = "";
    if (filteredItems.length > 0) {
      filteredItems.forEach((item) => {
        const dropdownItem = document.createElement("div");
        dropdownItem.className = "search-dropdown-item";

        dropdownItem.innerHTML = `
          <img src="${item.photo}" alt="${item.name}">
          <div class="search-dropdown-item-info">
            <div class="search-dropdown-item-name">${item.name}</div>
            <div class="search-dropdown-item-price">${formatPriceToTaka(
              item.price
            )}</div>
          </div>
          <button class="search-add-cart-btn" onclick="event.stopPropagation()">
            <i class="fa fa-plus"></i>
          </button>
        `;
        dropdownItem.addEventListener("click", function (e) {
          if (e.target.closest(".search-add-cart-btn")) {
            return;
          }
          searchBar.value = item.name;
          searchDropdown.style.display = "none";
          const menuSection = document.getElementById("menu");
          if (menuSection) {
            menuSection.scrollIntoView({ behavior: "smooth" });
          }
        });
        const addToCartBtn = dropdownItem.querySelector(".search-add-cart-btn");
        addToCartBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          addToCart({
            id: item.id,
            name: item.name,
            price: formatPriceToTaka(item.price),
            image: item.photo,
          });
          const originalText = addToCartBtn.innerHTML;
          addToCartBtn.innerHTML = '<i class="fa fa-check"></i>';
          addToCartBtn.style.background = "#4CAF50";

          setTimeout(() => {
            addToCartBtn.innerHTML = originalText;
            addToCartBtn.style.background = "#bfa46b";
          }, 1000);
        });

        searchDropdown.appendChild(dropdownItem);
      });

      searchDropdown.style.display = "block";
    } else {
      searchDropdown.innerHTML = `
        <div class="search-dropdown-item" style="cursor: default;">
          <div class="search-dropdown-item-info">
            <div class="search-dropdown-item-name">No results found</div>
          </div>
        </div>
      `;
      searchDropdown.style.display = "block";
    }
  });

  // Hide dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!searchBar.contains(e.target) && !searchDropdown.contains(e.target)) {
      searchDropdown.style.display = "none";
    }
  });

  // Hide dropdown when pressing Escape key
  searchBar.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      searchDropdown.style.display = "none";
      searchBar.blur();
    }
  });
});

let cart = [];

// Add functionality to menu "Add to Cart" buttons
document.addEventListener("DOMContentLoaded", function () {
  const menuCards = document.querySelectorAll(".menu-card");
  menuCards.forEach((card) => {
    const addCartBtn = card.querySelector(".add-cart-btn");
    if (addCartBtn) {
      addCartBtn.addEventListener("click", function () {
        const name = card.querySelector("h3").textContent;
        const price = card.querySelector(".menu-price").textContent;
        const image = card.querySelector(".menu-card-img").src;

        const item = { name, price, image };
        addToCart(item);
        const originalText = addCartBtn.textContent;
        addCartBtn.textContent = "Added!";
        addCartBtn.style.background = "#4CAF50";

        setTimeout(() => {
          addCartBtn.textContent = originalText;
          addCartBtn.style.background = "#bfa46b";
        }, 1000);
      });
    }
  });
});

// Cart modal controls
document.addEventListener("DOMContentLoaded", function () {
  const cartModal = document.getElementById("cart-modal");
  const cartBtn = document.querySelector(".cart-btn");
  const closeCartBtn = document.getElementById("close-cart-modal");
  const placeOrderBtn = document.getElementById("place-order-btn");
  if (cartBtn) {
    cartBtn.addEventListener("click", function () {
      cartModal.style.display = "flex";
      updateCartDisplay();
    });
  }
  if (closeCartBtn) {
    closeCartBtn.addEventListener("click", function () {
      cartModal.style.display = "none";
    });
  }
  cartModal.addEventListener("click", function (e) {
    if (e.target === cartModal) {
      cartModal.style.display = "none";
    }
  });
  if (placeOrderBtn) {
    placeOrderBtn.addEventListener("click", function () {
      if (cart.length === 0) return;
      const isLoggedIn = isUserLoggedIn();
      if (!isLoggedIn) {
        cartModal.style.display = "none";
        document.getElementById("signin-modal").style.display = "flex";
        alert(
          "Please sign in to place your order. Your cart items will be saved!"
        );
        return;
      }
      const userName = document.getElementById("dropdown-username").textContent;
      const orderTotal = document.getElementById("cart-total").textContent;
      const orderItems = cart
        .map((item) => `${item.name} (x${item.quantity})`)
        .join(", ");
      const confirmOrder = confirm(
        `Hi ${userName}!\n\nYour Order:\n${orderItems}\n\nTotal: ${orderTotal}\n\nConfirm your order?`
      );

      if (confirmOrder) {
        placeOrderBtn.disabled = true;
        placeOrderBtn.innerHTML =
          '<i class="fa fa-spinner fa-spin"></i> Processing Order...';
        const customerEmail =
          document.getElementById("dropdown-email").textContent;
        const customerMobile =
          document.getElementById("dropdown-mobile").textContent;
        const orderData = {
          customerName: userName,
          customerEmail: customerEmail,
          customerMobile: customerMobile,
          orderItems: cart.map((item) => ({
            name: item.name,
            quantity: item.quantity,
            price: item.price,
          })),
          orderTotal: orderTotal,
        };
        fetch("api/send_order_confirmation.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(orderData),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              onOrderPlacementSuccess(data.orderId);
            } else {
              alert(
                `Thank you ${userName}! Your order has been placed successfully!\n\nOrder Total: ${orderTotal}\n\nNote: There was an issue sending the confirmation email, but your order is confirmed.`
              );
            }
          })
          .catch((error) => {
            console.error("Email error:", error);
            alert(
              `Thank you ${userName}! Your order has been placed successfully!\n\nOrder Total: ${orderTotal}\n\nNote: There was an issue sending the confirmation email, but your order is confirmed.`
            );
          })
          .finally(() => {
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML =
              '<i class="fa fa-credit-card"></i> Place Order';
            cart = [];
            updateCartDisplay();
            updateCartButtonBadge();
            cartModal.style.display = "none";
          });
      }
    });
  }
});

// Add item to cart
function addToCart(item) {
  const existingItem = cart.find((cartItem) => cartItem.name === item.name);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({
      ...item,
      quantity: 1,
    });
  }

  updateCartDisplay();
  updateCartButtonBadge();
}

// Remove item from cart
function removeFromCart(itemName) {
  cart = cart.filter((item) => item.name !== itemName);
  updateCartDisplay();
  updateCartButtonBadge();
}

// Update item quantity
function updateQuantity(itemName, newQuantity) {
  if (newQuantity <= 0) {
    removeFromCart(itemName);
    return;
  }

  const item = cart.find((cartItem) => cartItem.name === itemName);
  if (item) {
    item.quantity = newQuantity;
    updateCartDisplay();
    updateCartButtonBadge();
  }
}

// Update cart display
function updateCartDisplay() {
  const cartItemsContainer = document.getElementById("cart-items");
  const cartSubtotal = document.getElementById("cart-subtotal");
  const cartTax = document.getElementById("cart-tax");
  const cartTotal = document.getElementById("cart-total");
  const placeOrderBtn = document.getElementById("place-order-btn");

  if (cart.length === 0) {
    cartItemsContainer.innerHTML = `
      <div class="empty-cart">
        <i class="fa fa-shopping-cart empty-cart-icon"></i>
        <p>Your cart is empty</p>
        <p class="empty-cart-subtitle">Add some delicious items to get started!</p>
      </div>
    `;
    cartSubtotal.textContent = "৳0.00";
    cartTax.textContent = "৳0.00";
    cartTotal.textContent = "৳0.00";
    placeOrderBtn.disabled = true;
    return;
  }

  // Generate cart items HTML
  const cartItemsHTML = cart
    .map(
      (item) => `
    <div class="cart-item">
      <img src="${item.image}" alt="${item.name}">
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-price">${item.price} each</div>
      </div>
      <div class="cart-item-controls">
        <div class="quantity-controls">
          <button class="quantity-btn" onclick="updateQuantity('${
            item.name
          }', ${item.quantity - 1})" ${item.quantity <= 1 ? "disabled" : ""}>
            <i class="fa fa-minus"></i>
          </button>
          <span class="quantity-display">${item.quantity}</span>
          <button class="quantity-btn" onclick="updateQuantity('${
            item.name
          }', ${item.quantity + 1})">
            <i class="fa fa-plus"></i>
          </button>
        </div>
        <button class="remove-item-btn" onclick="removeFromCart('${
          item.name
        }')">
          <i class="fa fa-trash"></i>
        </button>
      </div>
    </div>
  `
    )
    .join("");

  cartItemsContainer.innerHTML = cartItemsHTML;

  // Calculate totals
  const subtotal = cart.reduce((total, item) => {
    const price = parsePriceValue(item.price);
    return total + price * item.quantity;
  }, 0);

  const tax = subtotal * 0.1;
  const total = subtotal + tax;

  cartSubtotal.textContent = `৳${subtotal.toFixed(2)}`;
  cartTax.textContent = `৳${tax.toFixed(2)}`;
  cartTotal.textContent = `৳${total.toFixed(2)}`;
  placeOrderBtn.disabled = false;
}

// Update cart button badge
function updateCartButtonBadge() {
  const cartBtn = document.querySelector(".cart-btn");
  const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
  const existingBadge = cartBtn.querySelector(".cart-badge");
  if (existingBadge) {
    existingBadge.remove();
  }
  if (totalItems > 0) {
    const badge = document.createElement("span");
    badge.className = "cart-badge";
    badge.textContent = totalItems;
    cartBtn.appendChild(badge);
  }
}

// Utility function to validate email
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function isUserLoggedIn() {
  const userIcon = document.querySelector(".user-icon");
  const signinBtn = document.querySelector(".signin-btn");
  return (
    userIcon &&
    userIcon.style.display === "inline-block" &&
    signinBtn &&
    signinBtn.style.display === "none"
  );
}

// Order Sidebar Functions
function showOrderSidebar() {
  console.log("📂 showOrderSidebar() called");
  const sidebar = document.getElementById("order-sidebar");
  const floatingBtn = document.getElementById("floating-orders-btn");
  const body = document.body;

  if (sidebar) {
    console.log("📂 Sidebar element found, showing it");
    sidebar.classList.add("active");
    sidebar.style.display = "block";
    if (floatingBtn) {
      floatingBtn.classList.add("hidden");
    }
    console.log("📂 About to load user orders from showOrderSidebar");
    loadUserOrders();
  } else {
    console.error("❌ Sidebar element not found!");
  }
}
// Hide order sidebar
function hideOrderSidebar() {
  const sidebar = document.getElementById("order-sidebar");
  const floatingBtn = document.getElementById("floating-orders-btn");
  const body = document.body;

  if (sidebar) {
    sidebar.classList.remove("active");
    if (floatingBtn) {
      floatingBtn.classList.remove("hidden");
    }
  }
}

function toggleOrderSidebar() {
  const sidebar = document.getElementById("order-sidebar");
  if (sidebar && sidebar.classList.contains("active")) {
    hideOrderSidebar();
  } else {
    showOrderSidebar();
  }
}

// Load user orders from API
async function loadUserOrders() {
  console.log("🔍 loadUserOrders() called");
  const loadingElement = document.getElementById("sidebar-loading");
  const ordersListElement = document.getElementById("orders-list");
  const noOrdersElement = document.getElementById("no-orders");

  try {
    console.log("📡 Showing loading state");
    loadingElement.style.display = "block";
    ordersListElement.style.display = "none";
    noOrdersElement.style.display = "none";

    console.log("🌐 Fetching orders from API...");
    const response = await fetch("api/get_user_orders.php");
    console.log("📨 API Response status:", response.status);

    const data = await response.json();
    console.log("📋 API Response data:", data);

    loadingElement.style.display = "none";

    if (data.success && data.data && data.data.length > 0) {
      console.log("✅ Orders found, displaying them:", data.data.length);
      displayUserOrders(data.data);
      ordersListElement.style.display = "block";
    } else {
      console.log("❌ No orders found or API failed:", data);
      noOrdersElement.style.display = "block";
    }
  } catch (error) {
    console.error("💥 Error loading user orders:", error);
    loadingElement.style.display = "none";
    noOrdersElement.style.display = "block";
  }
}

// Display user orders in the sidebar
function displayUserOrders(orders) {
  const ordersListElement = document.getElementById("orders-list");

  ordersListElement.innerHTML = orders
    .map((order) => {
      const orderDate = new Date(order.created_at).toLocaleDateString();
      const itemsPreview =
        order.items
          .slice(0, 2)
          .map((item) => `${item.name} (x${item.quantity})`)
          .join(", ") + (order.items.length > 2 ? "..." : "");
      const normalizedStatus = normalizeOrderStatus(order.status);
      const statusClass = `status-${normalizedStatus}`;
      const cancelButton =
        normalizedStatus === "pending"
          ? `<button class="cancel-order-btn" onclick="event.stopPropagation(); cancelOrder(${order.id})" title="Cancel Order">
          <i class="fa fa-times"></i> Cancel
        </button>`
          : "";

      return `
      <div class="order-card" onclick="highlightOrder(${order.id})">
        <div class="order-card-header">
          <div class="order-number">#${order.order_number}</div>
          <div class="order-date">${orderDate}</div>
        </div>
        <div class="order-status ${statusClass}">${order.status}</div>
        <div class="order-items-preview">${itemsPreview}</div>
        <div class="order-total">৳${parseFloat(order.total).toFixed(2)}</div>
        ${cancelButton}
      </div>
    `;
    })
    .join("");
}

// Function to normalize order status for consistent CSS class naming
function normalizeOrderStatus(status) {
  if (!status) return "unknown";

  const normalizedStatus = status.toLowerCase().trim();
  switch (normalizedStatus) {
    case "pending":
    case "placed":
    case "ordered":
      return "pending";
    case "completed":
    case "complete":
    case "finished":
      return "completed";
    case "cancelled":
    case "canceled":
    case "cancel":
      return "canceled";
    case "preparing":
    case "cooking":
    case "in progress":
    case "in-progress":
      return "preparing";
    case "ready":
    case "ready for pickup":
    case "ready for delivery":
      return "ready";
    case "delivered":
    case "delivered successfully":
      return "delivered";
    case "rejected":
    case "declined":
    case "failed":
      return "rejected";
    default:
      return normalizedStatus;
  }
}

// Function to cancel an order
async function cancelOrder(orderId) {
  const confirmCancel = confirm("Are you sure you want to cancel this order?");

  if (!confirmCancel) {
    return;
  }

  try {
    const response = await fetch("api/cancel_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        order_id: orderId,
      }),
    });

    const data = await response.json();

    if (data.success) {
      alert("Order canceled successfully!");
      loadUserOrders();
    } else {
      alert("Error canceling order: " + data.message);
    }
  } catch (error) {
    console.error("Error canceling order:", error);
    alert("Error canceling order. Please try again.");
  }
}

// Function to highlight selected order
function highlightOrder(orderId) {
  const orderCards = document.querySelectorAll(".order-card");
  orderCards.forEach((card) => card.classList.remove("highlighted"));
  event.target.closest(".order-card").classList.add("highlighted");
  console.log("Selected order ID:", orderId);
}

// Function to handle actions after successful order placement
function onOrderPlacementSuccess(orderId) {
  console.log("🎉 Order placement success! Order ID:", orderId);
  alert("Order placed successfully! Your order is now visible in the sidebar.");
  setTimeout(() => {
    console.log("⏰ Timeout reached, showing sidebar and loading orders...");
    showOrderSidebar();
    loadUserOrders();
  }, 1500);
}

// Reservation Sidebar Functions
function showReservationSidebar() {
  console.log("📅 showReservationSidebar() called");
  const sidebar = document.getElementById("reservation-sidebar");
  const floatingBtn = document.getElementById("floating-reservations-btn");

  if (sidebar) {
    console.log("📅 Reservation sidebar element found, showing it");
    sidebar.classList.add("active");
    sidebar.style.display = "block";
    if (floatingBtn) {
      floatingBtn.classList.add("hidden");
    }
    console.log("📅 About to load user reservations");
    loadUserReservations();
  } else {
    console.error("❌ Reservation sidebar element not found!");
  }
}

// Hide reservation sidebar
function hideReservationSidebar() {
  const sidebar = document.getElementById("reservation-sidebar");
  const floatingBtn = document.getElementById("floating-reservations-btn");

  if (sidebar) {
    sidebar.classList.remove("active");
    if (floatingBtn) {
      floatingBtn.classList.remove("hidden");
    }
  }
}

// Toggle reservation sidebar
function toggleReservationSidebar() {
  const sidebar = document.getElementById("reservation-sidebar");
  if (sidebar && sidebar.classList.contains("active")) {
    hideReservationSidebar();
  } else {
    showReservationSidebar();
  }
}

// Load user reservations from API
async function loadUserReservations() {
  console.log("🔍 loadUserReservations() called");
  const loadingElement = document.getElementById("reservation-sidebar-loading");
  const reservationsList = document.getElementById("reservations-list");
  const noReservations = document.getElementById("no-reservations");

  if (!reservationsList) {
    console.error("❌ Reservations list element not found");
    return;
  }
  if (loadingElement) loadingElement.style.display = "block";
  if (noReservations) noReservations.style.display = "none";
  reservationsList.innerHTML = "";

  try {
    const response = await fetch("api/get_reservations.php");
    const data = await response.json();

    console.log("📅 Reservations response:", data);

    if (loadingElement) loadingElement.style.display = "none";

    if (data.success && data.data && data.data.length > 0) {
      data.data.forEach((reservation) => {
        const reservationCard = createReservationCard(reservation);
        reservationsList.appendChild(reservationCard);
      });
    } else {
      if (noReservations) noReservations.style.display = "block";
    }
  } catch (error) {
    console.error("Error loading reservations:", error);
    if (loadingElement) loadingElement.style.display = "none";
    if (noReservations) {
      noReservations.style.display = "block";
      noReservations.innerHTML = `
        <i class="fa fa-exclamation-triangle"></i>
        <p>Error loading reservations</p>
        <small>Please try again later</small>
      `;
    }
  }
}

// Create reservation card element
function createReservationCard(reservation) {
  const card = document.createElement("div");
  card.className = "reservation-card";
  card.setAttribute("data-reservation-id", reservation.id);
  const reservationDate = new Date(reservation.datee);
  const formattedDate = reservationDate.toLocaleDateString("en-US", {
    weekday: "short",
    year: "numeric",
    month: "short",
    day: "numeric",
  });
  let statusClass = "status-pending";
  let statusText = "Pending";

  if (reservation.tableno) {
    statusClass = "status-confirmed";
    statusText = "Confirmed";
  }

  card.innerHTML = `
    <div class="reservation-card-header">
      <span class="reservation-id">#${reservation.id}</span>
      <span class="reservation-status ${statusClass}">${statusText}</span>
    </div>
    <div class="reservation-details">
      <div class="reservation-details-row">
        <strong>Date:</strong>
        <span>${formattedDate}</span>
      </div>
      <div class="reservation-details-row">
        <strong>Time:</strong>
        <span>${reservation.times}</span>
      </div>
      <div class="reservation-details-row">
        <strong>Guests:</strong>
        <span>${reservation.guests} ${
    reservation.guests > 1 ? "people" : "person"
  }</span>
      </div>
      ${
        reservation.tableno
          ? `<div class="reservation-details-row">
               <strong>Table:</strong>
               <span>${reservation.tableno}</span>
             </div>`
          : `<div class="reservation-details-row">
               <strong>Table:</strong>
               <span style="color: #ffc107;">To be assigned</span>
             </div>`
      }
    </div>
    ${
      statusText === "Pending"
        ? `<div class="reservation-actions">
             <button class="reservation-cancel-btn" onclick="cancelReservation(${reservation.id})">
               <i class="fa fa-times"></i> Cancel Reservation
             </button>
           </div>`
        : statusText === "Confirmed"
        ? `<div class="reservation-actions">
              <div class="reservation-notice">
               <i class="fa fa-info-circle"></i>
               <span>Table assigned. For Cancel mail to : savore.2006@gmail.com</span>
             </div>
           </div>`
        : ""
    }
  `;

  return card;
}

// Cancel reservation function
async function cancelReservation(reservationId) {
  const reservationCard = document.querySelector(
    `[data-reservation-id="${reservationId}"]`
  );
  if (reservationCard) {
    const statusBadge = reservationCard.querySelector(".reservation-status");
    if (statusBadge && statusBadge.textContent.trim() === "Confirmed") {
      alert(
        "Cannot cancel confirmed reservations. Please contact the manager."
      );
      return;
    }
  }

  if (!confirm("Are you sure you want to cancel this reservation?")) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append("reservation_id", reservationId);

    const response = await fetch("api/cancel_reservation.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();
    if (data.success) {
      alert("Reservation cancelled successfully");
      loadUserReservations();
    } else {
      alert(data.message || "Failed to cancel reservation");
    }
  } catch (error) {
    console.error("Error cancelling reservation:", error);
    alert("An error occurred. Please try again.");
  }
}

// Event Listeners for Order Sidebar
document.addEventListener("DOMContentLoaded", function () {
  const toggleButton = document.getElementById("toggle-sidebar");
  if (toggleButton) {
    toggleButton.addEventListener("click", toggleOrderSidebar);
  }
  const floatingButton = document.getElementById("floating-orders-btn");
  if (floatingButton) {
    floatingButton.addEventListener("click", toggleOrderSidebar);
  }
  const reservationToggleButton = document.getElementById(
    "toggle-reservation-sidebar"
  );
  if (reservationToggleButton) {
    reservationToggleButton.addEventListener("click", toggleReservationSidebar);
  }

  const floatingReservationButton = document.getElementById(
    "floating-reservations-btn"
  );
  if (floatingReservationButton) {
    floatingReservationButton.addEventListener(
      "click",
      toggleReservationSidebar
    );
  }
  checkLoginStatusAndShowSidebar();
});

// Check login status and show/hide sidebar accordingly
function checkLoginStatusAndShowSidebar() {
  const userIcon = document.querySelector(".user-icon");
  const signinBtn = document.querySelector(".signin-btn");
  const floatingBtn = document.getElementById("floating-orders-btn");

  // Only show the floating button if logged in, but do NOT open the sidebar automatically
  if (
    userIcon &&
    userIcon.style.display === "inline-block" &&
    signinBtn &&
    signinBtn.style.display === "none"
  ) {
    hideOrderSidebar(); // Ensure sidebar is closed
    if (floatingBtn) {
      floatingBtn.style.display = "flex";
    }
  } else {
    hideOrderSidebar();
    if (floatingBtn) {
      floatingBtn.style.display = "none";
    }
  }
}

// Contact Form Functionality
document.addEventListener("DOMContentLoaded", function () {
  const contactForm = document.getElementById("contact-form");
  if (contactForm) {
    contactForm.addEventListener("submit", handleContactForm);
  }
});

function handleContactForm(e) {
  e.preventDefault();
  const form = e.target;
  const messageDiv = document.getElementById("contact-form-message");
  const submitBtn = form.querySelector(".send-btn");
  const formData = new FormData(form);
  const name = formData.get("name").trim();
  const email = formData.get("email").trim();
  const message = formData.get("message").trim();

  if (!name || !email || !message) {
    showContactMessage("Please fill in all required fields.", "error");
    return;
  }
  if (!isValidEmail(email)) {
    showContactMessage("Please enter a valid email address.", "error");
    return;
  }
  submitBtn.disabled = true;
  submitBtn.textContent = "Sending...";
  fetch("api/send_contact_message.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showContactMessage(data.message, "success");
        form.reset();
      } else {
        showContactMessage(data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Contact form error:", error);
      showContactMessage(
        "Sorry, there was an error sending your message. Please try again later.",
        "error"
      );
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.textContent = "Send A Message";
    });
}

function showContactMessage(message, type) {
  const messageDiv = document.getElementById("contact-form-message");
  if (messageDiv) {
    messageDiv.textContent = message;
    messageDiv.className = `message-container ${type}`;
    messageDiv.style.display = "block";
    if (type === "success") {
      setTimeout(() => {
        messageDiv.style.display = "none";
      }, 5000);
    }
  }
}
// Review Form Functionality
document.addEventListener("DOMContentLoaded", function () {
  const reviewForm = document.getElementById("review-form");
  if (reviewForm) {
    reviewForm.addEventListener("submit", handleReviewForm);
  }
  initializeStarRating();
});

// Initialize star rating functionality
function initializeStarRating() {
  const stars = document.querySelectorAll(".star-rating .star");
  const starsInput = document.getElementById("review-stars");

  stars.forEach((star, index) => {
    const starValue = index + 1;

    star.addEventListener("click", function () {
      starsInput.value = starValue;
      updateStarDisplay(starValue);
    });
    star.addEventListener("mouseenter", function () {
      updateStarDisplay(starValue, true);
    });
  });
  const starRating = document.querySelector(".star-rating");
  if (starRating) {
    starRating.addEventListener("mouseleave", function () {
      const currentRating = parseInt(starsInput.value) || 0;
      updateStarDisplay(currentRating);
    });
  }
}

// Update star display
function updateStarDisplay(rating, isHover = false) {
  const stars = document.querySelectorAll(".star-rating .star i");

  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.remove("fa-regular");
      star.classList.add("fa-solid");
      star.style.color = isHover ? "#ffed4a" : "#ffd700";
    } else {
      star.classList.remove("fa-solid");
      star.classList.add("fa-regular");
      star.style.color = "#ccc";
    }
  });
}

// Handle review form submission
function handleReviewForm(e) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);
  const name = formData.get("name").trim();
  const message = formData.get("message").trim();
  const stars = parseInt(formData.get("stars"));
  if (!name || !message) {
    showReviewMessage("Please fill in your name and review message.", "error");
    return;
  }

  if (stars < 1 || stars > 5) {
    showReviewMessage("Please select a star rating.", "error");
    return;
  }

  if (name.length > 50) {
    showReviewMessage("Name must be 50 characters or less.", "error");
    return;
  }
  submitBtn.disabled = true;
  submitBtn.textContent = "Submitting...";

  fetch("api/submit_review.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showReviewMessage(data.message, "success");
        form.reset();
        resetStarRating();
      } else {
        showReviewMessage(data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Review form error:", error);
      showReviewMessage(
        "Sorry, there was an error submitting your review. Please try again later.",
        "error"
      );
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.textContent = "Submit Review";
    });
}

// Show review message to user
function showReviewMessage(message, type) {
  const messageDiv = document.getElementById("review-message-display");
  if (messageDiv) {
    messageDiv.textContent = message;
    messageDiv.className = `message-container ${type}`;
    messageDiv.style.display = "block";
    if (type === "success") {
      setTimeout(() => {
        messageDiv.style.display = "none";
      }, 5000);
    }
  }
}

// Reset star rating to empty state
function resetStarRating() {
  document.querySelectorAll(".star-rating .star i").forEach((icon) => {
    icon.classList.remove("fa-solid");
    icon.classList.add("fa-regular");
    icon.style.color = "#ccc";
  });
  const starsInput = document.getElementById("review-stars");
  if (starsInput) {
    starsInput.value = "0";
  }
}

// Load and display reviews on page load
async function loadReviews() {
  const reviewsList = document.getElementById("reviews-list");
  if (reviewsList) {
    reviewsList.innerHTML = "";
  }
  return;
}

// Display reviews in the reviews section
function displayReviews(reviews) {
  const reviewsList = document.getElementById("reviews-list");

  const reviewsHTML = reviews
    .map((review) => {
      const starsHTML = generateStarsHTML(review.stars);
      return `
      <div class="review-item">
        <div class="review-header">
          <div class="review-name">${escapeHtml(review.name)}</div>
          <div class="review-date">${review.date_formatted}</div>
        </div>
        <div class="review-stars">${starsHTML}</div>
        <div class="review-message">${escapeHtml(review.message)}</div>
      </div>
    `;
    })
    .join("");

  reviewsList.innerHTML = reviewsHTML;
}

// Generate star HTML for display
function generateStarsHTML(rating) {
  let starsHTML = "";
  for (let i = 1; i <= 5; i++) {
    if (i <= rating) {
      starsHTML +=
        '<i class="fa fa-star fa-solid" style="color: #ffd700;"></i>';
    } else {
      starsHTML += '<i class="fa fa-star fa-regular" style="color: #ccc;"></i>';
    }
  }
  return starsHTML;
}

// Escape HTML to prevent XSS
function escapeHtml(unsafe) {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
